<?php

namespace App\Livewire\Administration;

use App\Enums\DataImportRowStatus;
use App\Enums\DataImportStatus;
use App\Enums\PermissionName;
use App\Models\DataImportBatch;
use App\Services\Imports\LegacyProjectImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class ImportBatchReview extends Component
{
    use WithPagination;

    public int $batchId;
    public array $columnMap = [];
    public string $rowStatus = 'all';

    public function mount(int $batchId): void
    {
        Gate::authorize(PermissionName::DataImportsView->value);

        $batch = DataImportBatch::query()->findOrFail($batchId);
        $this->batchId = $batch->id;
        $savedMap = $batch->header_map ?? [];
        $this->columnMap = [];
        foreach (($batch->headers ?? []) as $index => $header) {
            $this->columnMap[$index] = $savedMap[$header] ?? '';
        }
    }

    public function updatedRowStatus(): void
    {
        $this->resetPage();
    }

    public function autoMap(): void
    {
        Gate::authorize(PermissionName::DataImportsManage->value);
        $batch = $this->batch();
        $auto = app(LegacyProjectImportService::class)->autoMap($batch->headers ?? []);
        $this->columnMap = [];
        foreach (($batch->headers ?? []) as $index => $header) {
            $this->columnMap[$index] = $auto[$header] ?? '';
        }
        $this->resetValidation();
    }

    public function validateDryRun(): void
    {
        Gate::authorize(PermissionName::DataImportsManage->value);
        $batch = $this->batch();

        if (in_array($batch->status, [DataImportStatus::Completed, DataImportStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'mapping' => 'This import batch is closed and can no longer be revalidated.',
            ]);
        }

        app(LegacyProjectImportService::class)->validateBatch($batch, $this->mappingForService($batch));
        session()->flash('import-status', 'Dry-run validation completed. Review all warnings, duplicates, and errors before committing.');
        $this->resetPage();
    }

    public function commitImport(): void
    {
        Gate::authorize(PermissionName::DataImportsManage->value);
        $batch = $this->batch();

        app(LegacyProjectImportService::class)->commit($batch, auth()->id());
        session()->flash('import-status', 'Legacy import committed successfully. Existing projects were not overwritten.');
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::DataImportsView->value);

        $batch = DataImportBatch::query()
            ->with(['uploader', 'committer'])
            ->findOrFail($this->batchId);

        $rows = $batch->rows()
            ->with(['duplicateProject', 'importedProject'])
            ->when($this->rowStatus !== 'all', fn ($query) => $query->where('status', $this->rowStatus))
            ->orderBy('source_sheet')
            ->orderBy('row_number')
            ->paginate(20);

        return view('livewire.administration.import-batch-review', [
            'batch' => $batch,
            'rows' => $rows,
            'fieldOptions' => config('legacy_import.fields', []),
            'requiredFields' => config('legacy_import.required_fields', []),
            'rowStatuses' => DataImportRowStatus::cases(),
        ]);
    }

    private function mappingForService(DataImportBatch $batch): array
    {
        $mapping = [];
        foreach (($batch->headers ?? []) as $index => $header) {
            $mapping[$header] = $this->columnMap[$index] ?? '';
        }

        return $mapping;
    }

    private function batch(): DataImportBatch
    {
        return DataImportBatch::query()->findOrFail($this->batchId);
    }
}
