<?php

namespace App\Livewire\Beneficiaries;

use App\Enums\BeneficiarySex;
use App\Enums\PermissionName;
use App\Models\BeneficiarySector;
use App\Models\Office;
use App\Models\ProjectBeneficiary;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sex = '';
    public string $sector = '';
    public string $office = '';

    public function mount(): void
    {
        Gate::authorize(PermissionName::BeneficiariesView->value);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'sex', 'sector', 'office'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::BeneficiariesView->value);

        $base = ProjectBeneficiary::query()
            ->with(['project.proponent', 'project.office', 'project.primaryLocation.province', 'sectors'])
            ->when(filled($this->search), function (Builder $query): void {
                $search = trim($this->search);
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('project', fn (Builder $projectQuery) => $projectQuery->where('title', 'like', "%{$search}%"));
                });
            })
            ->when(filled($this->sex), fn (Builder $query) => $query->where('sex', $this->sex))
            ->when(filled($this->sector), fn (Builder $query) => $query->whereHas('sectors', fn (Builder $sectorQuery) => $sectorQuery->whereKey((int) $this->sector)))
            ->when(filled($this->office), fn (Builder $query) => $query->whereHas('project', fn (Builder $projectQuery) => $projectQuery->where('office_id', (int) $this->office)));

        $summaryQuery = clone $base;
        $total = $summaryQuery->count();
        $active = (clone $base)->where('is_active', true)->count();
        $projects = (clone $base)->distinct('project_id')->count('project_id');

        $beneficiaries = $base
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20);

        return view('livewire.beneficiaries.index', [
            'beneficiaries' => $beneficiaries,
            'sexes' => BeneficiarySex::cases(),
            'sectors' => BeneficiarySector::query()->active()->ordered()->get(),
            'offices' => Office::query()->active()->ordered()->get(),
            'summary' => [
                'total' => $total,
                'active' => $active,
                'projects' => $projects,
            ],
        ]);
    }
}
