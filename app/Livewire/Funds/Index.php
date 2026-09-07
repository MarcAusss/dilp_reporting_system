<?php

namespace App\Livewire\Funds;

use App\Enums\PermissionName;
use App\Models\FundSource;
use App\Models\FundTarget;
use App\Models\Office;
use App\Models\Project;
use App\Models\ProjectBeneficiary;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public int $year;
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $fund_source_id = '';
    public string $office_id = '';
    public string $allocation_amount = '0';
    public int $target_projects = 0;
    public int $target_beneficiaries = 0;
    public string $remarks = '';

    public function mount(): void
    {
        Gate::authorize(PermissionName::FundTargetsView->value);
        $this->year = (int) now()->year;
    }

    public function startCreate(): void
    {
        Gate::authorize(PermissionName::FundTargetsUpdate->value);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize(PermissionName::FundTargetsUpdate->value);
        $target = FundTarget::query()->findOrFail($id);
        $this->editingId = $target->id;
        $this->year = $target->fiscal_year;
        $this->fund_source_id = (string) $target->fund_source_id;
        $this->office_id = $target->office_id ? (string) $target->office_id : '';
        $this->allocation_amount = (string) $target->allocation_amount;
        $this->target_projects = $target->target_projects;
        $this->target_beneficiaries = $target->target_beneficiaries;
        $this->remarks = $target->remarks ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize(PermissionName::FundTargetsUpdate->value);

        $scopeKey = FundTarget::scopeKey(filled($this->office_id) ? (int) $this->office_id : null);

        $validated = $this->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:'.((int) now()->year + 2)],
            'fund_source_id' => [
                'required',
                'integer',
                'exists:fund_sources,id',
                Rule::unique('fund_targets', 'fund_source_id')
                    ->where(fn ($query) => $query->where('fiscal_year', $this->year)->where('scope_key', $scopeKey))
                    ->ignore($this->editingId),
            ],
            'office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'allocation_amount' => ['required', 'numeric', 'min:0', 'max:9999999999999999.99'],
            'target_projects' => ['required', 'integer', 'min:0', 'max:1000000'],
            'target_beneficiaries' => ['required', 'integer', 'min:0', 'max:100000000'],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        $data = [
            'fiscal_year' => (int) $validated['year'],
            'fund_source_id' => (int) $validated['fund_source_id'],
            'office_id' => filled($validated['office_id'] ?? null) ? (int) $validated['office_id'] : null,
            'scope_key' => $scopeKey,
            'allocation_amount' => round((float) $validated['allocation_amount'], 2),
            'target_projects' => (int) $validated['target_projects'],
            'target_beneficiaries' => (int) $validated['target_beneficiaries'],
            'remarks' => filled($validated['remarks'] ?? null) ? trim($validated['remarks']) : null,
            'updated_by' => auth()->id(),
        ];

        if ($this->editingId) {
            $target = FundTarget::query()->findOrFail($this->editingId);
            $old = $target->only(['fiscal_year','fund_source_id','office_id','allocation_amount','target_projects','target_beneficiaries','remarks']);
            $target->update($data);
            app(AuditLogService::class)->record('fund_target', 'updated', 'Updated DILP fund target #'.$target->id.'.', $target, $old, $data);
            session()->flash('fund-status', 'Fund target updated successfully.');
        } else {
            $target = FundTarget::query()->create([...$data, 'created_by' => auth()->id()]);
            app(AuditLogService::class)->record('fund_target', 'created', 'Created DILP fund target #'.$target->id.'.', $target, null, $data);
            session()->flash('fund-status', 'Fund target created successfully.');
        }

        $this->resetForm();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::FundTargetsView->value);

        $targets = FundTarget::query()
            ->with(['fundSource', 'office'])
            ->where('fiscal_year', $this->year)
            ->orderBy('office_id')
            ->orderBy('fund_source_id')
            ->get()
            ->map(function (FundTarget $target): FundTarget {
                $projects = Project::query()
                    ->with(['financial'])
                    ->withCount(['beneficiaries' => fn ($query) => $query->where('is_active', true)])
                    ->where('fiscal_year', $target->fiscal_year)
                    ->where('fund_source_id', $target->fund_source_id)
                    ->when($target->office_id, fn ($query) => $query->where('office_id', $target->office_id))
                    ->get();

                $actualDole = $projects->sum(fn (Project $project): float => $project->financial?->doleShare() ?? 0);
                $actualBeneficiaries = $projects->sum('beneficiaries_count');

                $target->setAttribute('actual_projects', $projects->count());
                $target->setAttribute('actual_beneficiaries', $actualBeneficiaries);
                $target->setAttribute('actual_dole_share', round($actualDole, 2));
                $target->setAttribute('allocation_balance', round((float) $target->allocation_amount - $actualDole, 2));
                return $target;
            });

        $years = collect(range((int) now()->year + 1, max(2000, (int) now()->year - 10)))
            ->merge(FundTarget::query()->distinct()->pluck('fiscal_year'))
            ->unique()->sortDesc()->values();

        $actualProjects = Project::query()
            ->where('fiscal_year', $this->year)
            ->count();

        $actualBeneficiaries = ProjectBeneficiary::query()
            ->where('is_active', true)
            ->whereHas('project', fn ($query) => $query->where('fiscal_year', $this->year))
            ->count();

        return view('livewire.funds.index', [
            'targets' => $targets,
            'years' => $years,
            'fundSources' => FundSource::query()->active()->ordered()->get(),
            'offices' => Office::query()->active()->ordered()->get(),
            'summary' => [
                'allocation' => $targets->sum(fn ($item) => (float) $item->allocation_amount),
                'target_projects' => $targets->sum('target_projects'),
                'actual_projects' => $actualProjects,
                'target_beneficiaries' => $targets->sum('target_beneficiaries'),
                'actual_beneficiaries' => $actualBeneficiaries,
            ],
        ]);
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->fund_source_id = '';
        $this->office_id = '';
        $this->allocation_amount = '0';
        $this->target_projects = 0;
        $this->target_beneficiaries = 0;
        $this->remarks = '';
        $this->resetValidation();
    }
}
