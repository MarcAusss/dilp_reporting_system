<?php

namespace App\Livewire\Projects;

use App\Enums\PermissionName;
use App\Models\Livelihood;
use App\Models\Project;
use App\Models\ProjectLivelihood;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class LivelihoodDetails extends Component
{
    public int $projectId;

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $livelihood_id = null;

    public bool $is_primary = false;

    public int $target_beneficiaries = 0;

    public string $description = '';

    public string $remarks = '';

    public function mount(int $projectId): void
    {
        Gate::authorize(PermissionName::ProjectLivelihoodsView->value);
        Project::query()->findOrFail($projectId);
        $this->projectId = $projectId;
    }

    public function startCreate(): void
    {
        Gate::authorize(PermissionName::ProjectLivelihoodsUpdate->value);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize(PermissionName::ProjectLivelihoodsUpdate->value);

        $record = ProjectLivelihood::query()
            ->where('project_id', $this->projectId)
            ->findOrFail($id);

        $this->editingId = $record->id;
        $this->livelihood_id = $record->livelihood_id;
        $this->is_primary = $record->is_primary;
        $this->target_beneficiaries = $record->target_beneficiaries;
        $this->description = $record->description ?? '';
        $this->remarks = $record->remarks ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize(PermissionName::ProjectLivelihoodsUpdate->value);

        $validated = $this->validate([
            'livelihood_id' => [
                'required',
                'integer',
                Rule::exists('livelihoods', 'id')->where('is_active', true),
                Rule::unique('project_livelihoods', 'livelihood_id')
                    ->where(fn ($query) => $query->where('project_id', $this->projectId))
                    ->ignore($this->editingId),
            ],
            'is_primary' => ['required', 'boolean'],
            'target_beneficiaries' => ['required', 'integer', 'min:0', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:3000'],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        DB::transaction(function () use ($validated): void {
            if ($validated['is_primary']) {
                ProjectLivelihood::query()
                    ->where('project_id', $this->projectId)
                    ->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))
                    ->update(['is_primary' => false]);
            }

            $record = $this->editingId
                ? ProjectLivelihood::query()
                    ->where('project_id', $this->projectId)
                    ->findOrFail($this->editingId)
                : new ProjectLivelihood([
                    'project_id' => $this->projectId,
                    'created_by' => auth()->id(),
                ]);

            $record->fill([
                'livelihood_id' => $validated['livelihood_id'],
                'is_primary' => $validated['is_primary'],
                'target_beneficiaries' => $validated['target_beneficiaries'],
                'description' => $this->nullableTrim($validated['description'] ?? null),
                'remarks' => $this->nullableTrim($validated['remarks'] ?? null),
                'updated_by' => auth()->id(),
            ])->save();
        });

        session()->flash('livelihood-status', 'Project livelihood details saved successfully.');
        $this->resetForm();
    }

    public function remove(int $id): void
    {
        Gate::authorize(PermissionName::ProjectLivelihoodsUpdate->value);

        ProjectLivelihood::query()
            ->where('project_id', $this->projectId)
            ->findOrFail($id)
            ->delete();

        session()->flash('livelihood-status', 'Livelihood entry removed.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::ProjectLivelihoodsView->value);

        return view('livewire.projects.livelihood-details', [
            'project' => Project::query()
                ->with(['proponent', 'projectType', 'projectPurpose'])
                ->findOrFail($this->projectId),
            'records' => ProjectLivelihood::query()
                ->with('livelihood')
                ->where('project_id', $this->projectId)
                ->orderByDesc('is_primary')
                ->orderBy('id')
                ->get(),
            'livelihoodOptions' => Livelihood::query()->active()->ordered()->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->livelihood_id = null;
        $this->is_primary = false;
        $this->target_beneficiaries = 0;
        $this->description = '';
        $this->remarks = '';
        $this->showForm = false;
        $this->resetValidation();
    }

    private function nullableTrim(mixed $value): ?string
    {
        return filled($value) ? trim((string) $value) : null;
    }
}
