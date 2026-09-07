<?php

namespace App\Livewire\Administration;

use App\Enums\DataImportStatus;
use App\Enums\PermissionName;
use App\Models\DataImportBatch;
use App\Models\DataImportRow;
use App\Services\AuditLogService;
use App\Services\Imports\LegacyProjectImportService;
use App\Services\Imports\LegacySpreadsheetReader;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class ImportManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $upload = null;

    public function mount(): void
    {
        Gate::authorize(PermissionName::DataImportsView->value);
    }

    public function uploadBatch(): void
    {
        Gate::authorize(PermissionName::DataImportsManage->value);

        $maxKb = max(1024, ((int) config('legacy_import.max_file_mb', 25)) * 1024);

        $validated = $this->validate([
            'upload' => [
                'required',
                'file',
                'max:'.$maxKb,
                'mimes:csv,xlsx',
            ],
        ]);

        $file = $validated['upload'];
        $extension = strtolower($file->getClientOriginalExtension());
        $storedPath = $file->storeAs(
            'legacy-imports',
            Str::uuid().'.'.$extension,
            'local'
        );

        if (! $storedPath) {
            throw ValidationException::withMessages([
                'upload' => 'The legacy spreadsheet could not be stored.',
            ]);
        }

        try {
            $parsed = app(LegacySpreadsheetReader::class)->read(
                Storage::disk('local')->path($storedPath),
                $extension
            );

            if ($parsed['rows'] === []) {
                throw ValidationException::withMessages([
                    'upload' => 'The uploaded file contains headers but no data rows.',
                ]);
            }

            $maxRows = (int) config('legacy_import.max_rows', 50000);
            if (count($parsed['rows']) > $maxRows) {
                throw ValidationException::withMessages([
                    'upload' => 'This file contains '.number_format(count($parsed['rows'])).' rows. The configured maximum is '.number_format($maxRows).'. Split the workbook into smaller batches.',
                ]);
            }

            $autoMap = app(LegacyProjectImportService::class)->autoMap($parsed['headers']);

            $batch = DataImportBatch::query()->create([
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'file_type' => $extension,
                'status' => DataImportStatus::Uploaded,
                'total_rows' => count($parsed['rows']),
                'headers' => $parsed['headers'],
                'header_map' => $autoMap,
                'options' => [
                    'create_only' => true,
                    'source' => 'legacy_spreadsheet',
                ],
                'uploaded_by' => auth()->id(),
            ]);

            foreach (array_chunk($parsed['rows'], 500) as $chunk) {
                $now = now();
                $records = array_map(fn (array $row): array => [
                    'batch_id' => $batch->id,
                    'source_sheet' => $row['sheet'],
                    'row_number' => $row['row_number'],
                    'raw_data' => json_encode($row['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk);

                DataImportRow::query()->insert($records);
            }

            app(AuditLogService::class)->record(
                'import',
                'uploaded',
                sprintf('Uploaded legacy spreadsheet %s as import batch #%d.', $batch->original_filename, $batch->id),
                $batch,
                null,
                ['total_rows' => $batch->total_rows, 'file_type' => $extension],
            );

            $this->upload = null;

            $this->redirectRoute('data-imports.review', ['batch' => $batch->id], navigate: true);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPath);
            throw $exception;
        }
    }

    public function cancelBatch(int $batchId): void
    {
        Gate::authorize(PermissionName::DataImportsManage->value);

        $batch = DataImportBatch::query()->findOrFail($batchId);

        if ($batch->status === DataImportStatus::Completed) {
            throw ValidationException::withMessages([
                'import' => 'Completed import batches are retained for audit and cannot be cancelled.',
            ]);
        }

        $batch->update([
            'status' => DataImportStatus::Cancelled,
            'completed_at' => now(),
        ]);

        app(AuditLogService::class)->record(
            'import',
            'cancelled',
            'Cancelled legacy import batch #'.$batch->id.'.',
            $batch,
        );

        session()->flash('import-status', 'Import batch cancelled. Its audit history is retained.');
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::DataImportsView->value);

        $batches = DataImportBatch::query()
            ->with(['uploader', 'committer'])
            ->latest('id')
            ->paginate(15);

        return view('livewire.administration.import-manager', [
            'batches' => $batches,
            'maxFileMb' => (int) config('legacy_import.max_file_mb', 25),
        ]);
    }
}
