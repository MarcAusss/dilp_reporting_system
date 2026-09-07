<?php

namespace App\Services\Imports;

use App\Enums\DataImportRowStatus;
use App\Enums\DataImportStatus;
use App\Enums\ProjectRecordStatus;
use App\Enums\ProponentType;
use App\Models\Barangay;
use App\Models\DataImportBatch;
use App\Models\DataImportRow;
use App\Models\FundSource;
use App\Models\ImplementationMode;
use App\Models\Livelihood;
use App\Models\Municipality;
use App\Models\Office;
use App\Models\Project;
use App\Models\ProjectPurpose;
use App\Models\ProjectType;
use App\Models\Proponent;
use App\Models\Province;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LegacyProjectImportService
{
    public function autoMap(array $headers): array
    {
        $aliases = config('legacy_import.aliases', []);
        $map = [];

        foreach ($headers as $header) {
            $normalizedHeader = $this->normalizeKey($header);
            $matched = '';

            foreach ($aliases as $field => $fieldAliases) {
                foreach ($fieldAliases as $alias) {
                    if ($normalizedHeader === $this->normalizeKey($alias)) {
                        $matched = $field;
                        break 2;
                    }
                }
            }

            $map[$header] = $matched;
        }

        return $map;
    }

    public function validateBatch(DataImportBatch $batch, array $headerMap): DataImportBatch
    {
        $this->assertRequiredMappings($headerMap);

        $batch->update([
            'status' => DataImportStatus::Validating,
            'header_map' => $headerMap,
            'failure_message' => null,
        ]);

        $seenBatchKeys = [];
        $counters = [
            'valid' => 0,
            'warning' => 0,
            'error' => 0,
            'duplicate' => 0,
        ];

        $batch->rows()
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($headerMap, &$seenBatchKeys, &$counters): void {
                foreach ($rows as $row) {
                    $normalized = $this->normalizeRow($row->raw_data ?? [], $headerMap);
                    $normalized['_source_row'] = $row->row_number;
                    $result = $this->validateNormalizedRow($normalized, $seenBatchKeys);

                    $row->update([
                        'normalized_data' => $normalized,
                        'status' => $result['status'],
                        'messages' => $result['messages'],
                        'duplicate_project_id' => $result['duplicate_project_id'],
                        'imported_project_id' => null,
                    ]);

                    $counters[$result['status']->value]++;
                }
            });

        $status = $counters['error'] > 0
            ? DataImportStatus::HasErrors
            : DataImportStatus::Ready;

        $batch->update([
            'status' => $status,
            'valid_rows' => $counters['valid'],
            'warning_rows' => $counters['warning'],
            'error_rows' => $counters['error'],
            'duplicate_rows' => $counters['duplicate'],
            'imported_rows' => 0,
            'skipped_rows' => 0,
            'validated_at' => now(),
            'validation_summary' => [
                'create_only' => true,
                'required_mappings_present' => true,
                'master_data_policy' => 'Existing master data must resolve by name or code. Proponents may be created.',
                'duplicate_policy' => 'Existing or repeated project matches are never overwritten.',
            ],
        ]);

        app(AuditLogService::class)->record(
            'import',
            'validated',
            sprintf('Validated legacy import batch #%d (%s).', $batch->id, $batch->original_filename),
            $batch,
            null,
            [
                'valid_rows' => $counters['valid'],
                'warning_rows' => $counters['warning'],
                'error_rows' => $counters['error'],
                'duplicate_rows' => $counters['duplicate'],
            ],
        );

        return $batch->refresh();
    }

    public function commit(DataImportBatch $batch, int $userId): DataImportBatch
    {
        $batch->refresh();

        if (! $batch->canCommit()) {
            throw ValidationException::withMessages([
                'import' => 'This batch cannot be committed until all validation errors and duplicates are resolved.',
            ]);
        }

        $batch->update([
            'status' => DataImportStatus::Importing,
            'started_at' => now(),
            'committed_by' => $userId,
            'failure_message' => null,
        ]);

        $imported = 0;
        $skipped = $batch->duplicate_rows;

        try {
            DB::transaction(function () use ($batch, $userId, &$imported, &$skipped): void {
                $batch->rows()
                    ->whereIn('status', [DataImportRowStatus::Valid->value, DataImportRowStatus::Warning->value])
                    ->orderBy('id')
                    ->chunkById(500, function ($rows) use ($batch, $userId, &$imported, &$skipped): void {
                        foreach ($rows as $row) {
                            $normalized = $row->normalized_data ?? [];
                            $duplicate = $this->findDuplicateProject($normalized);

                            if ($duplicate) {
                                $row->update([
                                    'status' => DataImportRowStatus::Skipped,
                                    'duplicate_project_id' => $duplicate->id,
                                    'messages' => array_values(array_unique([
                                        ...($row->messages ?? []),
                                        'Skipped during commit because a matching project now exists.',
                                    ])),
                                ]);
                                $skipped++;
                                continue;
                            }

                            $project = $this->createProjectFromRow($batch, $row, $normalized, $userId);

                            $row->update([
                                'status' => DataImportRowStatus::Imported,
                                'imported_project_id' => $project->id,
                            ]);

                            $imported++;
                        }
                    });

                $batch->update([
                    'status' => DataImportStatus::Completed,
                    'imported_rows' => $imported,
                    'skipped_rows' => $skipped,
                    'completed_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            $batch->update([
                'status' => DataImportStatus::Failed,
                'failure_message' => Str::limit($exception->getMessage(), 3000),
                'completed_at' => now(),
            ]);

            app(AuditLogService::class)->record(
                'import',
                'failed',
                sprintf('Legacy import batch #%d failed during commit.', $batch->id),
                $batch,
                null,
                ['message' => $exception->getMessage()],
                $userId,
            );

            throw $exception;
        }

        app(AuditLogService::class)->record(
            'import',
            'committed',
            sprintf('Committed legacy import batch #%d.', $batch->id),
            $batch,
            null,
            ['imported_rows' => $imported, 'skipped_rows' => $skipped],
            $userId,
        );

        return $batch->refresh();
    }

    private function assertRequiredMappings(array $headerMap): void
    {
        $mapped = array_values(array_filter($headerMap));
        $missing = [];

        foreach (config('legacy_import.required_fields', []) as $required) {
            if (! in_array($required, $mapped, true)) {
                $missing[] = config('legacy_import.fields.'.$required, $required);
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'mapping' => 'Map all required fields before validation: '.implode(', ', $missing).'.',
            ]);
        }

        $duplicates = array_filter(array_count_values($mapped), fn (int $count): bool => $count > 1);

        if ($duplicates !== []) {
            $labels = array_map(
                fn (string $field): string => config('legacy_import.fields.'.$field, $field),
                array_keys($duplicates)
            );

            throw ValidationException::withMessages([
                'mapping' => 'Each destination field may only be mapped once. Duplicate mappings: '.implode(', ', $labels).'.',
            ]);
        }
    }

    private function normalizeRow(array $raw, array $headerMap): array
    {
        $data = [];

        foreach ($headerMap as $source => $destination) {
            if (! $destination) {
                continue;
            }

            $value = $raw[$source] ?? null;
            $data[$destination] = is_string($value) ? trim($value) : $value;
        }

        foreach (['equipment_materials_tools', 'insurance', 'training', 'proponent_partner_equity', 'beneficiary_equity'] as $moneyField) {
            if (array_key_exists($moneyField, $data)) {
                $data[$moneyField] = $this->normalizeMoney($data[$moneyField]);
            }
        }

        if (array_key_exists('target_beneficiaries', $data)) {
            $data['target_beneficiaries'] = $this->normalizeInteger($data['target_beneficiaries']);
        }

        if (array_key_exists('fiscal_year', $data)) {
            $data['fiscal_year'] = $this->normalizeYear($data['fiscal_year']);
        }

        if (! empty($data['date_received'])) {
            $data['date_received'] = $this->normalizeDate($data['date_received']);
        }

        if (! empty($data['record_status'])) {
            $data['record_status'] = $this->normalizeRecordStatus($data['record_status']);
        }

        if (! empty($data['proponent_type'])) {
            $data['proponent_type'] = $this->normalizeProponentType($data['proponent_type']);
        }

        return $data;
    }

    private function validateNormalizedRow(array $data, array &$seenBatchKeys): array
    {
        $errors = [];
        $warnings = [];
        $duplicateProjectId = null;

        foreach (config('legacy_import.required_fields', []) as $field) {
            if (! isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $errors[] = config('legacy_import.fields.'.$field, $field).' is required.';
            }
        }

        $year = (int) ($data['fiscal_year'] ?? 0);
        if ($year < 2000 || $year > ((int) date('Y') + 2)) {
            $errors[] = 'Fiscal Year must be between 2000 and '.(((int) date('Y')) + 2).'.';
        }

        if (($data['date_received'] ?? null) === '__invalid_date__') {
            $errors[] = 'Date Received is not a recognized date.';
        }

        $resolved = [];

        if (! empty($data['project_type'])) {
            $resolved['project_type_id'] = $this->resolveMasterData(ProjectType::class, $data['project_type'])?->id;
            if (! $resolved['project_type_id']) {
                $errors[] = 'Project Type "'.$data['project_type'].'" was not found in Master Data.';
            }
        }

        if (! empty($data['project_purpose'])) {
            $resolved['project_purpose_id'] = $this->resolveMasterData(ProjectPurpose::class, $data['project_purpose'])?->id;
            if (! $resolved['project_purpose_id']) {
                $errors[] = 'Project Purpose "'.$data['project_purpose'].'" was not found in Master Data.';
            }
        }

        foreach ([
            'office' => [Office::class, 'office_id', 'Office'],
            'fund_source' => [FundSource::class, 'fund_source_id', 'Fund Source'],
            'implementation_mode' => [ImplementationMode::class, 'implementation_mode_id', 'Implementation Mode'],
            'livelihood' => [Livelihood::class, 'livelihood_id', 'Livelihood'],
        ] as $field => [$class, $idField, $label]) {
            if (! empty($data[$field])) {
                $resolved[$idField] = $this->resolveMasterData($class, $data[$field])?->id;
                if (! $resolved[$idField]) {
                    $errors[] = $label.' "'.$data[$field].'" was not found in Master Data.';
                }
            }
        }

        $province = null;
        $municipality = null;
        $barangay = null;

        if (! empty($data['province'])) {
            $province = $this->resolveMasterData(Province::class, $data['province']);
            if (! $province) {
                $errors[] = 'Province "'.$data['province'].'" was not found.';
            } else {
                $resolved['province_id'] = $province->id;
            }
        }

        if (! empty($data['municipality'])) {
            if (! $province) {
                $errors[] = 'Province is required when Municipality/City is supplied.';
            } else {
                $municipality = Municipality::query()
                    ->where('province_id', $province->id)
                    ->where(function ($query) use ($data): void {
                        $query->whereRaw('LOWER(name) = ?', [mb_strtolower($data['municipality'])])
                            ->orWhereRaw('LOWER(code) = ?', [mb_strtolower($data['municipality'])]);
                    })
                    ->first();

                if (! $municipality) {
                    $errors[] = 'Municipality/City "'.$data['municipality'].'" was not found under '.$province->name.'.';
                } else {
                    $resolved['municipality_id'] = $municipality->id;
                }
            }
        }

        if (! empty($data['barangay'])) {
            if (! $municipality) {
                $errors[] = 'Municipality/City is required when Barangay is supplied.';
            } else {
                $barangay = Barangay::query()
                    ->where('municipality_id', $municipality->id)
                    ->where(function ($query) use ($data): void {
                        $query->whereRaw('LOWER(name) = ?', [mb_strtolower($data['barangay'])])
                            ->orWhereRaw('LOWER(code) = ?', [mb_strtolower($data['barangay'])]);
                    })
                    ->first();

                if (! $barangay) {
                    $errors[] = 'Barangay "'.$data['barangay'].'" was not found under '.$municipality->name.'.';
                } else {
                    $resolved['barangay_id'] = $barangay->id;
                }
            }
        }

        foreach (['equipment_materials_tools', 'insurance', 'training', 'proponent_partner_equity', 'beneficiary_equity'] as $moneyField) {
            if (($data[$moneyField] ?? 0) < 0) {
                $errors[] = config('legacy_import.fields.'.$moneyField, $moneyField).' cannot be negative.';
            }
        }

        if (($data['target_beneficiaries'] ?? 0) < 0) {
            $errors[] = 'Target Beneficiaries cannot be negative.';
        }

        if (($data['target_beneficiaries'] ?? 0) > 0 && empty($data['livelihood'])) {
            $warnings[] = 'Target Beneficiaries is present but no Livelihood Type is mapped; the aggregate target will not create a livelihood record.';
        }

        $data['_resolved'] = $resolved;
        $duplicate = $this->findDuplicateProject($data);

        if ($duplicate) {
            $duplicateProjectId = $duplicate->id;
            $warnings[] = 'A matching project already exists: '.$duplicate->registry_number.' — '.$duplicate->title.'.';
        }

        $batchKey = $this->batchDuplicateKey($data);
        if ($batchKey && isset($seenBatchKeys[$batchKey])) {
            $warnings[] = 'This project appears more than once in the uploaded file (first seen on row '.$seenBatchKeys[$batchKey].').';
            $duplicateProjectId ??= null;
        } elseif ($batchKey) {
            $seenBatchKeys[$batchKey] = $data['_source_row'] ?? '?';
        }

        if ($errors !== []) {
            return [
                'status' => DataImportRowStatus::Error,
                'messages' => [...$errors, ...$warnings],
                'duplicate_project_id' => $duplicateProjectId,
            ];
        }

        if ($duplicate || count(array_filter($warnings, fn (string $message): bool => str_contains($message, 'more than once'))) > 0) {
            return [
                'status' => DataImportRowStatus::Duplicate,
                'messages' => $warnings,
                'duplicate_project_id' => $duplicateProjectId,
            ];
        }

        return [
            'status' => $warnings !== [] ? DataImportRowStatus::Warning : DataImportRowStatus::Valid,
            'messages' => $warnings,
            'duplicate_project_id' => null,
        ];
    }

    private function createProjectFromRow(DataImportBatch $batch, DataImportRow $row, array $data, int $userId): Project
    {
        $resolved = $this->resolveReferencesForCommit($data);
        $proponent = $this->resolveOrCreateProponent($data);

        $project = Project::query()->create([
            'fiscal_year' => (int) $data['fiscal_year'],
            'project_code' => $this->nullableTrim($data['project_code'] ?? null),
            'title' => trim((string) $data['title']),
            'proponent_id' => $proponent->id,
            'office_id' => $resolved['office_id'] ?? null,
            'fund_source_id' => $resolved['fund_source_id'] ?? null,
            'project_type_id' => $resolved['project_type_id'],
            'project_purpose_id' => $resolved['project_purpose_id'],
            'implementation_mode_id' => $resolved['implementation_mode_id'] ?? null,
            'date_received' => ($data['date_received'] ?? null) && $data['date_received'] !== '__invalid_date__' ? $data['date_received'] : null,
            'record_status' => $data['record_status'] ?? ProjectRecordStatus::Active->value,
            'source_reference' => $this->nullableTrim($data['source_reference'] ?? null)
                ?? sprintf('LEGACY-BATCH-%d-%s-%d', $batch->id, Str::slug($row->source_sheet ?: 'sheet'), $row->row_number),
            'remarks' => $this->nullableTrim($data['remarks'] ?? null),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        if (isset($resolved['province_id'])) {
            $project->locations()->create([
                'province_id' => $resolved['province_id'],
                'municipality_id' => $resolved['municipality_id'] ?? null,
                'barangay_id' => $resolved['barangay_id'] ?? null,
                'address_detail' => $this->nullableTrim($data['address_detail'] ?? null),
                'is_primary' => true,
            ]);
        }

        $financialData = [
            'equipment_materials_tools' => (float) ($data['equipment_materials_tools'] ?? 0),
            'insurance' => (float) ($data['insurance'] ?? 0),
            'training' => (float) ($data['training'] ?? 0),
            'proponent_partner_equity' => (float) ($data['proponent_partner_equity'] ?? 0),
            'beneficiary_equity' => (float) ($data['beneficiary_equity'] ?? 0),
        ];

        if (array_sum($financialData) > 0) {
            $project->financial()->create([
                ...$financialData,
                'remarks' => 'Imported from legacy DILP spreadsheet batch #'.$batch->id.'.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        if (! empty($resolved['livelihood_id'])) {
            $project->livelihoods()->create([
                'livelihood_id' => $resolved['livelihood_id'],
                'is_primary' => true,
                'target_beneficiaries' => max(0, (int) ($data['target_beneficiaries'] ?? 0)),
                'remarks' => 'Imported from legacy DILP spreadsheet batch #'.$batch->id.'.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        app(AuditLogService::class)->record(
            'project',
            'legacy_import_create',
            'Created project from legacy import batch #'.$batch->id.'.',
            $project,
            null,
            ['batch_id' => $batch->id, 'row_id' => $row->id],
            $userId,
        );

        return $project;
    }

    private function resolveReferencesForCommit(array $data): array
    {
        $resolved = [];
        $resolved['project_type_id'] = $this->resolveMasterData(ProjectType::class, $data['project_type'])?->id;
        $resolved['project_purpose_id'] = $this->resolveMasterData(ProjectPurpose::class, $data['project_purpose'])?->id;

        foreach ([
            'office' => [Office::class, 'office_id'],
            'fund_source' => [FundSource::class, 'fund_source_id'],
            'implementation_mode' => [ImplementationMode::class, 'implementation_mode_id'],
            'livelihood' => [Livelihood::class, 'livelihood_id'],
        ] as $field => [$class, $idField]) {
            if (! empty($data[$field])) {
                $resolved[$idField] = $this->resolveMasterData($class, $data[$field])?->id;
            }
        }

        if (! empty($data['province'])) {
            $province = $this->resolveMasterData(Province::class, $data['province']);
            $resolved['province_id'] = $province?->id;

            if ($province && ! empty($data['municipality'])) {
                $municipality = Municipality::query()
                    ->where('province_id', $province->id)
                    ->where(function ($query) use ($data): void {
                        $query->whereRaw('LOWER(name) = ?', [mb_strtolower($data['municipality'])])
                            ->orWhereRaw('LOWER(code) = ?', [mb_strtolower($data['municipality'])]);
                    })
                    ->first();
                $resolved['municipality_id'] = $municipality?->id;

                if ($municipality && ! empty($data['barangay'])) {
                    $resolved['barangay_id'] = Barangay::query()
                        ->where('municipality_id', $municipality->id)
                        ->where(function ($query) use ($data): void {
                            $query->whereRaw('LOWER(name) = ?', [mb_strtolower($data['barangay'])])
                                ->orWhereRaw('LOWER(code) = ?', [mb_strtolower($data['barangay'])]);
                        })
                        ->value('id');
                }
            }
        }

        if (! $resolved['project_type_id'] || ! $resolved['project_purpose_id']) {
            throw ValidationException::withMessages([
                'import' => 'A required master-data reference changed after validation. Re-run validation before importing.',
            ]);
        }

        return $resolved;
    }

    private function resolveOrCreateProponent(array $data): Proponent
    {
        $name = trim((string) $data['proponent_name']);
        $existing = Proponent::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return $existing;
        }

        return Proponent::query()->create([
            'type' => $data['proponent_type'] ?? ProponentType::Organization->value,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function findDuplicateProject(array $data): ?Project
    {
        $year = (int) ($data['fiscal_year'] ?? 0);
        $code = $this->nullableTrim($data['project_code'] ?? null);

        if ($year && $code) {
            $byCode = Project::withTrashed()
                ->where('fiscal_year', $year)
                ->whereRaw('LOWER(project_code) = ?', [mb_strtolower($code)])
                ->first();

            if ($byCode) {
                return $byCode;
            }
        }

        $title = $this->nullableTrim($data['title'] ?? null);
        $proponent = $this->nullableTrim($data['proponent_name'] ?? null);

        if (! $year || ! $title || ! $proponent) {
            return null;
        }

        return Project::withTrashed()
            ->where('fiscal_year', $year)
            ->whereRaw('LOWER(title) = ?', [mb_strtolower($title)])
            ->whereHas('proponent', fn ($query) => $query->whereRaw('LOWER(name) = ?', [mb_strtolower($proponent)]))
            ->first();
    }

    private function batchDuplicateKey(array $data): ?string
    {
        $year = (int) ($data['fiscal_year'] ?? 0);
        $code = $this->nullableTrim($data['project_code'] ?? null);

        if ($year && $code) {
            return $year.'|code|'.mb_strtolower($code);
        }

        $title = $this->nullableTrim($data['title'] ?? null);
        $proponent = $this->nullableTrim($data['proponent_name'] ?? null);

        return $year && $title && $proponent
            ? $year.'|title|'.mb_strtolower($title).'|'.mb_strtolower($proponent)
            : null;
    }

    private function resolveMasterData(string $class, string $value): ?Model
    {
        $normalized = mb_strtolower(trim($value));

        return $class::query()
            ->where(function ($query) use ($normalized): void {
                $query->whereRaw('LOWER(name) = ?', [$normalized])
                    ->orWhereRaw('LOWER(code) = ?', [$normalized]);
            })
            ->first();
    }

    private function normalizeMoney(mixed $value): float
    {
        if ($value === null || trim((string) $value) === '') {
            return 0;
        }

        $cleaned = preg_replace('/[^0-9.\-()]/', '', (string) $value);
        $negativeParentheses = str_contains($cleaned, '(') && str_contains($cleaned, ')');
        $cleaned = str_replace(['(', ')'], '', $cleaned);
        $number = is_numeric($cleaned) ? (float) $cleaned : 0;

        return round($negativeParentheses ? -abs($number) : $number, 2);
    }

    private function normalizeInteger(mixed $value): int
    {
        $cleaned = preg_replace('/[^0-9\-]/', '', (string) $value);
        return is_numeric($cleaned) ? (int) $cleaned : 0;
    }

    private function normalizeYear(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (preg_match('/(20\d{2})/', (string) $value, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function normalizeDate(mixed $value): string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return '';
        }

        if (is_numeric($raw) && (float) $raw > 20000 && (float) $raw < 80000) {
            return Carbon::create(1899, 12, 30)->addDays((int) floor((float) $raw))->format('Y-m-d');
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (Throwable) {
            return '__invalid_date__';
        }
    }

    private function normalizeRecordStatus(string $value): string
    {
        $normalized = $this->normalizeKey($value);

        return match ($normalized) {
            'archived', 'archive', 'inactive' => ProjectRecordStatus::Archived->value,
            'draft' => ProjectRecordStatus::Draft->value,
            default => ProjectRecordStatus::Active->value,
        };
    }

    private function normalizeProponentType(string $value): string
    {
        $normalized = $this->normalizeKey($value);

        return match (true) {
            str_contains($normalized, 'individual') => ProponentType::Individual->value,
            str_contains($normalized, 'group') => ProponentType::Group->value,
            default => ProponentType::Organization->value,
        };
    }

    private function normalizeKey(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value);
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function nullableTrim(mixed $value): ?string
    {
        return filled($value) ? trim((string) $value) : null;
    }
}
