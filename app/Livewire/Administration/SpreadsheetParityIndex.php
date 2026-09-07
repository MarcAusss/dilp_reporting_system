<?php

namespace App\Livewire\Administration;

use App\Enums\PermissionName;
use App\Enums\SpreadsheetParityStatus;
use App\Services\SpreadsheetParityService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class SpreadsheetParityIndex extends Component
{
    public string $search = '';
    public string $sheetFilter = '';
    public string $coverageFilter = '';
    public string $domainFilter = '';

    public function mount(): void
    {
        Gate::authorize(PermissionName::SpreadsheetParityView->value);
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'sheetFilter',
            'coverageFilter',
            'domainFilter',
        ]);
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::SpreadsheetParityView->value);

        $service = app(SpreadsheetParityService::class);
        $fields = $service->filteredFields(
            $this->search,
            $this->sheetFilter,
            $this->coverageFilter,
            $this->domainFilter,
        );

        return view('livewire.administration.spreadsheet-parity-index', [
            'workbook' => $service->workbook(),
            'summary' => $service->summary(),
            'sheets' => $service->sheets(),
            'domains' => $service->domains(),
            'statuses' => SpreadsheetParityStatus::cases(),
            'fields' => $fields,
            'filteredCount' => $fields->count(),
        ]);
    }
}
