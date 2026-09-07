<?php

namespace App\Livewire\Projects;

use App\Enums\PermissionName;
use App\Models\ConvergenceProgram;
use App\Models\Project;
use App\Models\ProjectConvergence;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ConvergenceDetails extends Component
{
    public int $projectId;

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $convergence_program_id = null;

    public string $reference_number = '';

    public string $assistance_description = '';

    public string $assistance_amount = '0.00';

    public string $assistance_date = '';

    public string $remarks = '';

    public function mount(int $projectId): void
    {
        Gate::authorize(PermissionName::ProjectConvergenceView->value);
        Project::query()->findOrFail($projectId);
        $this->projectId = $projectId;
    }

    public function startCreate(): void
    {
        Gate::authorize(PermissionName::ProjectConvergenceUpdate->value);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize(PermissionName::ProjectConvergenceUpdate->value);

        $record = ProjectConvergence::query()
            ->where('project_id', $this->projectId)
            ->findOrFail($id);

        $this->editingId = $record->id;
        $this->convergence_program_id = $record->convergence_program_id;
        $this->reference_number = $record->reference_number ?? '';
        $this->assistance_description = $record->assistance_description ?? '';
        $this->assistance_amount = number_format((float) $record->assistance_amount, 2, '.', '');
        $this->assistance_date = $record->assistance_date?->format('Y-m-d') ?? '';
        $this->remarks = $record->remarks ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize(PermissionName::ProjectConvergenceUpdate->value);

        $validated = $this->validate([
            'convergence_program_id' => [
                'required',
                'integer',
                Rule::exists('convergence_programs', 'id')->where('is_active', true),
            ],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'assistance_description' => ['nullable', 'string', 'max:3000'],
            'assistance_amount' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'assistance_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        $record = $this->editingId
            ? ProjectConvergence::query()
                ->where('project_id', $this->projectId)
                ->findOrFail($this->editingId)
            : new ProjectConvergence([
                'project_id' => $this->projectId,
                'created_by' => auth()->id(),
            ]);

        $record->fill([
            'convergence_program_id' => $validated['convergence_program_id'],
            'reference_number' => $this->nullableTrim($validated['reference_number'] ?? null),
            'assistance_description' => $this->nullableTrim($validated['assistance_description'] ?? null),
            'assistance_amount' => $validated['assistance_amount'],
            'assistance_date' => filled($validated['assistance_date'] ?? null)
                ? $validated['assistance_date']
                : null,
            'remarks' => $this->nullableTrim($validated['remarks'] ?? null),
            'updated_by' => auth()->id(),
        ])->save();

        session()->flash('convergence-status', 'Convergence record saved successfully.');
        $this->resetForm();
    }

    public function remove(int $id): void
    {
        Gate::authorize(PermissionName::ProjectConvergenceUpdate->value);

        ProjectConvergence::query()
            ->where('project_id', $this->projectId)
            ->findOrFail($id)
            ->delete();

        session()->flash('convergence-status', 'Convergence record removed.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::ProjectConvergenceView->value);

        $records = ProjectConvergence::query()
            ->with('program')
            ->where('project_id', $this->projectId)
            ->latest('assistance_date')
            ->latest('id')
            ->get();

        return view('livewire.projects.convergence-details', [
            'project' => Project::query()
                ->with(['proponent', 'projectType', 'projectPurpose'])
                ->findOrFail($this->projectId),
            'records' => $records,
            'programs' => ConvergenceProgram::query()->active()->ordered()->get(),
            'totalAssistance' => round(
                (float) $records->sum(fn (ProjectConvergence $record): float => (float) $record->assistance_amount),
                2
            ),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->convergence_program_id = null;
        $this->reference_number = '';
        $this->assistance_description = '';
        $this->assistance_amount = '0.00';
        $this->assistance_date = '';
        $this->remarks = '';
        $this->showForm = false;
        $this->resetValidation();
    }

    private function nullableTrim(mixed $value): ?string
    {
        return filled($value) ? trim((string) $value) : null;
    }
}
