<?php

namespace App\Livewire\Administration;

use App\Enums\PermissionName;
use App\Services\DataQualityService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class DataQualityIndex extends Component
{
    public string $selectedCheck = 'missing_location';

    public function mount(): void
    {
        Gate::authorize(PermissionName::DataQualityView->value);
    }

    public function selectCheck(string $check): void
    {
        Gate::authorize(PermissionName::DataQualityView->value);
        abort_unless(array_key_exists($check, app(DataQualityService::class)->checks()), 404);
        $this->selectedCheck = $check;
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::DataQualityView->value);

        $service = app(DataQualityService::class);
        $summary = $service->summary();

        if (! array_key_exists($this->selectedCheck, $summary)) {
            $this->selectedCheck = array_key_first($summary);
        }

        return view('livewire.administration.data-quality-index', [
            'summary' => $summary,
            'projects' => $service->projectsFor($this->selectedCheck),
            'current' => $summary[$this->selectedCheck],
        ]);
    }
}
