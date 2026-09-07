<?php

namespace Database\Seeders;

use App\Enums\ProjectBudgetComponent;
use App\Enums\ProjectRecordStatus;
use App\Enums\ProjectWorkflowAction;
use App\Enums\ProjectWorkflowStage;
use App\Enums\ProjectWorkflowStatus;
use App\Enums\ProponentType;
use App\Enums\UserRole;
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
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class LegacySpreadsheetProjectSeeder extends Seeder
{
    private const SOURCE_WORKBOOK = 'Y2026 DILP DATABASE.xlsx';

    private const SOURCE_SHEET = 'WORKING SHEET (ALL 2026 PROJECTS)';

    /**
     * Seed 20 real project rows extracted from the legacy FY2026 DILP workbook.
     *
     * This seeder is intentionally idempotent and non-destructive. If a project
     * with the same FY/project code already exists, that project is left intact.
     * Aggregate beneficiary counts from the workbook are preserved as livelihood
     * targets; fake person-level beneficiary records are never generated.
     *
     * @throws JsonException
     */
    public function run(): void
    {
        $this->call(MasterDataSeeder::class);

        $rows = $this->sourceRows();

        if (count($rows) < 20) {
            throw new RuntimeException(
                'LegacySpreadsheetProjectSeeder requires at least 20 source project rows.'
            );
        }

        $actor = User::query()
            ->whereHas('roles', fn ($query) => $query
                ->where('name', UserRole::SuperAdmin->value))
            ->orderBy('id')
            ->first();

        foreach ($rows as $row) {
            DB::transaction(function () use ($row, $actor): void {
                $this->seedProject($row, $actor);
            });
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws JsonException
     */
    private function sourceRows(): array
    {
        $path = database_path(
            'seeders/data/y2026_dilp_seed_projects.json'
        );

        if (! is_file($path)) {
            throw new RuntimeException(
                'Legacy DILP seeder data file is missing: '.$path
            );
        }

        $decoded = json_decode(
            file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (! is_array($decoded)) {
            throw new RuntimeException(
                'Legacy DILP seeder data could not be decoded.'
            );
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function seedProject(array $row, ?User $actor): void
    {
        $projectCode = trim((string) ($row['project_code'] ?? ''));

        if ($projectCode === '') {
            return;
        }

        $existing = Project::query()
            ->where('fiscal_year', 2026)
            ->where('project_code', $projectCode)
            ->first();

        if ($existing) {
            return;
        }

        $province = $this->province(
            (string) ($row['province'] ?? 'Unspecified')
        );

        $municipality = $this->municipality(
            $province,
            (string) ($row['municipalities'] ?? '')
        );

        $office = $this->officeForProvince($province->name);

        $fundSource = FundSource::updateOrCreate(
            ['name' => (string) $row['fund_source']],
            [
                'code' => 'Y2026_REGULAR_GAA',
                'description' => 'FY2026 Regular GAA Fund from the legacy DILP workbook.',
                'is_active' => true,
                'sort_order' => 10,
            ]
        );

        $implementationMode = $this->implementationMode(
            (string) ($row['implementation_mode'] ?? '')
        );

        $projectType = ProjectType::query()
            ->where('code', Str::upper((string) $row['project_type']))
            ->firstOrFail();

        $projectPurpose = ProjectPurpose::query()
            ->where('code', Str::upper((string) $row['purpose']))
            ->firstOrFail();

        $proponent = Proponent::firstOrCreate(
            [
                'name' => trim((string) $row['proponent_name']),
            ],
            [
                'type' => ProponentType::Organization,
                'address' => $province->name,
                'is_active' => true,
            ]
        );

        $project = Project::create([
            'fiscal_year' => 2026,
            'project_code' => $projectCode,
            'title' => trim((string) $row['title']),
            'proponent_id' => $proponent->id,
            'office_id' => $office->id,
            'fund_source_id' => $fundSource->id,
            'project_type_id' => $projectType->id,
            'project_purpose_id' => $projectPurpose->id,
            'implementation_mode_id' => $implementationMode?->id,
            'date_received' => $row['date_received'] ?: null,
            'record_status' => ProjectRecordStatus::Active,
            'source_reference' => sprintf(
                '%s / source row %d',
                self::SOURCE_WORKBOOK,
                (int) $row['source_row']
            ),
            'remarks' => $this->legacyRemarks($row),
            'created_by' => $actor?->id,
            'updated_by' => $actor?->id,
        ]);

        $project->locations()->create([
            'province_id' => $province->id,
            'municipality_id' => $municipality?->id,
            'barangay_id' => null,
            'address_detail' => $this->locationDetail($row),
            'is_primary' => true,
        ]);

        $financial = $project->financial()->create([
            'equipment_materials_tools' => $this->money($row['equipment_materials_tools'] ?? 0),
            'insurance' => $this->money($row['insurance'] ?? 0),
            'training' => $this->money($row['training'] ?? 0),
            'proponent_partner_equity' => $this->money($row['proponent_partner_equity'] ?? 0),
            'beneficiary_equity' => $this->money($row['beneficiary_equity'] ?? 0),
            'remarks' => sprintf(
                'Aggregate values seeded from %s, source row %d.',
                self::SOURCE_WORKBOOK,
                (int) $row['source_row']
            ),
            'created_by' => $actor?->id,
            'updated_by' => $actor?->id,
        ]);

        $this->seedBudgetItems($project, $financial->only([
            'equipment_materials_tools',
            'insurance',
            'training',
            'proponent_partner_equity',
            'beneficiary_equity',
        ]), $actor, (int) $row['source_row']);

        $livelihoodName = $this->primaryLivelihoodName(
            (string) ($row['livelihoods'] ?? '')
        );

        if ($livelihoodName !== null) {
            $livelihood = Livelihood::updateOrCreate(
                ['name' => $livelihoodName],
                [
                    'is_active' => true,
                    'sort_order' => 900,
                ]
            );

            $project->livelihoods()->create([
                'livelihood_id' => $livelihood->id,
                'is_primary' => true,
                'target_beneficiaries' => max(
                    0,
                    (int) ($row['beneficiary_count'] ?? 0)
                ),
                'description' => (string) ($row['livelihoods'] ?? ''),
                'remarks' => sprintf(
                    'Aggregate beneficiary target from legacy workbook. Female beneficiaries reported: %d.',
                    max(0, (int) ($row['female_beneficiary_count'] ?? 0))
                ),
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);
        }

        $this->applyLegacyWorkflowSnapshot(
            $project,
            trim((string) ($row['legacy_status'] ?? '')),
            $actor,
            (int) $row['source_row']
        );
    }

    private function province(string $name): Province
    {
        $name = trim($name) ?: 'Unspecified';

        return Province::updateOrCreate(
            ['name' => $name],
            [
                'code' => Str::limit(
                    Str::upper(Str::slug($name, '_')),
                    50,
                    ''
                ),
                'is_active' => true,
                'sort_order' => 100,
            ]
        );
    }

    private function municipality(
        Province $province,
        string $rawMunicipalities
    ): ?Municipality {
        $name = $this->primaryMunicipalityName($rawMunicipalities);

        if ($name === null) {
            return null;
        }

        $code = Str::limit(
            Str::upper(
                Str::slug($province->name.'_'.$name, '_')
            ),
            50,
            ''
        );

        return Municipality::updateOrCreate(
            [
                'province_id' => $province->id,
                'name' => $name,
            ],
            [
                'code' => $code,
                'type' => Str::contains(
                    Str::lower($name),
                    'city'
                ) ? 'city' : 'municipality',
                'is_active' => true,
                'sort_order' => 100,
            ]
        );
    }

    private function officeForProvince(string $province): Office
    {
        $map = [
            'Albay' => ['APO', 'DOLE Albay Provincial Office'],
            'Camarines Norte' => ['CNPO', 'DOLE Camarines Norte Provincial Office'],
            'Camarines Sur' => ['CSPO', 'DOLE Camarines Sur Provincial Office'],
            'Catanduanes' => ['CTPO', 'DOLE Catanduanes Provincial Office'],
            'Masbate' => ['MPO', 'DOLE Masbate Provincial Office'],
            'Sorsogon' => ['SPO', 'DOLE Sorsogon Provincial Office'],
        ];

        [$code, $name] = $map[$province] ?? [
            Str::limit(Str::upper(Str::slug($province, '_')), 45, '').'_PO',
            'DOLE '.$province.' Office',
        ];

        return Office::updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'description' => 'Office scope used by the FY2026 legacy project seeder.',
                'is_active' => true,
                'sort_order' => 100,
            ]
        );
    }

    private function implementationMode(string $name): ?ImplementationMode
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        return ImplementationMode::updateOrCreate(
            ['name' => $name],
            [
                'code' => Str::limit(
                    Str::upper(Str::slug($name, '_')),
                    50,
                    ''
                ),
                'is_active' => true,
                'sort_order' => 100,
            ]
        );
    }

    private function primaryMunicipalityName(string $raw): ?string
    {
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        $first = preg_split('/\R|\//u', $raw, 2)[0] ?? $raw;
        $first = preg_replace('/\s*=\s*\d+.*$/u', '', $first) ?? $first;
        $first = preg_replace('/\s*-\s*\d+\s*$/u', '', $first) ?? $first;
        $first = trim($first, " \t\n\r\0\x0B,-=");

        return $first !== ''
            ? Str::limit($first, 150, '')
            : null;
    }

    private function primaryLivelihoodName(string $raw): ?string
    {
        $raw = trim($raw);

        if ($raw === '' || Str::upper($raw) === 'N/A') {
            return null;
        }

        $first = preg_split('/\R|\//u', $raw, 2)[0] ?? $raw;
        $first = preg_replace('/\s*=\s*\d+.*$/u', '', $first) ?? $first;
        $first = preg_replace('/\s*-\s*\d+\s*$/u', '', $first) ?? $first;
        $first = preg_replace('/\s*\(\s*\d+\s*\)\s*$/u', '', $first) ?? $first;
        $first = trim($first, " \t\n\r\0\x0B,-=");

        return $first !== ''
            ? Str::limit($first, 150, '')
            : null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function legacyRemarks(array $row): string
    {
        $parts = [
            sprintf(
                'Seeded from %s, %s, source row %d.',
                self::SOURCE_WORKBOOK,
                self::SOURCE_SHEET,
                (int) $row['source_row']
            ),
            'Legacy status: '.((string) ($row['legacy_status'] ?: 'Not specified')).'.',
            sprintf(
                'Legacy beneficiary count: %d; female beneficiaries: %d.',
                max(0, (int) ($row['beneficiary_count'] ?? 0)),
                max(0, (int) ($row['female_beneficiary_count'] ?? 0))
            ),
        ];

        if (filled($row['beneficiary_sectors'] ?? null)) {
            $parts[] = 'Legacy beneficiary sectors: '.trim((string) $row['beneficiary_sectors']);
        }

        if (filled($row['livelihoods'] ?? null)) {
            $parts[] = 'Legacy livelihood detail: '.trim((string) $row['livelihoods']);
        }

        if (filled($row['municipalities'] ?? null)) {
            $parts[] = 'Legacy municipality detail: '.trim((string) $row['municipalities']);
        }

        return implode("\n\n", $parts);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function locationDetail(array $row): ?string
    {
        $barangays = trim((string) ($row['barangays'] ?? ''));

        if ($barangays === '') {
            return null;
        }

        return Str::limit(
            'Legacy barangay detail: '.$barangays,
            255,
            ''
        );
    }

    /**
     * @param  array<string, mixed>  $financial
     */
    private function seedBudgetItems(
        Project $project,
        array $financial,
        ?User $actor,
        int $sourceRow
    ): void {
        $components = [
            [
                ProjectBudgetComponent::EquipmentMaterialsTools,
                'equipment_materials_tools',
                'Legacy aggregate: Equipment / Materials / Tools',
            ],
            [
                ProjectBudgetComponent::Insurance,
                'insurance',
                'Legacy aggregate: Insurance',
            ],
            [
                ProjectBudgetComponent::Training,
                'training',
                'Legacy aggregate: Training',
            ],
            [
                ProjectBudgetComponent::ProponentPartnerEquity,
                'proponent_partner_equity',
                'Legacy aggregate: Proponent / Partner Equity',
            ],
            [
                ProjectBudgetComponent::BeneficiaryEquity,
                'beneficiary_equity',
                'Legacy aggregate: Beneficiary Equity',
            ],
        ];

        foreach ($components as [$component, $column, $description]) {
            $amount = $this->money($financial[$column] ?? 0);

            if ($amount <= 0) {
                continue;
            }

            $project->budgetItems()->create([
                'component' => $component,
                'item_description' => $description,
                'quantity' => 1,
                'unit' => 'lot',
                'unit_cost' => $amount,
                'amount' => $amount,
                'remarks' => sprintf(
                    'Aggregate amount copied from %s, source row %d.',
                    self::SOURCE_WORKBOOK,
                    $sourceRow
                ),
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);
        }
    }

    private function applyLegacyWorkflowSnapshot(
        Project $project,
        string $legacyStatus,
        ?User $actor,
        int $sourceRow
    ): void {
        if (! Str::contains(
            Str::upper($legacyStatus),
            ['APPROVED', 'PAID']
        )) {
            return;
        }

        $state = $project->workflowState()->first();

        if (! $state) {
            return;
        }

        $fromStage = $state->stage;
        $fromStatus = $state->status;
        $actedAt = $project->date_received?->endOfDay() ?? now();

        $state->update([
            'stage' => ProjectWorkflowStage::Completed,
            'status' => ProjectWorkflowStatus::Completed,
            'assigned_role' => null,
            'last_action_at' => $actedAt,
            'updated_by' => $actor?->id,
        ]);

        $project->workflowEvents()->create([
            'from_stage' => $fromStage,
            'from_status' => $fromStatus,
            'to_stage' => ProjectWorkflowStage::Completed,
            'to_status' => ProjectWorkflowStatus::Completed,
            'action' => ProjectWorkflowAction::Approved,
            'assigned_role' => null,
            'reference_number' => sprintf('LEGACY-ROW-%d', $sourceRow),
            'remarks' => sprintf(
                'Legacy workbook snapshot status: %s. Intermediate historical transitions were not reconstructed by the demo seeder.',
                $legacyStatus
            ),
            'acted_by' => $actor?->id,
            'acted_at' => $actedAt,
        ]);
    }

    private function money(mixed $value): float
    {
        return round(max(0, (float) $value), 2);
    }
}
