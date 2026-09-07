<?php

namespace App\Livewire\Projects;

use App\Enums\PermissionName;
use App\Enums\ProjectWorkflowAction;
use App\Enums\ProjectWorkflowStage;
use App\Models\Project;
use App\Services\ProjectWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class WorkflowDetails extends Component
{
    use WithPagination;

    public int $projectId;

    public string $reference_number = '';

    public string $remarks = '';

    public function mount(int $projectId): void
    {
        Gate::authorize(PermissionName::ProjectWorkflowView->value);

        $project = Project::query()->findOrFail($projectId);

        app(ProjectWorkflowService::class)->ensureState($project);

        $this->projectId = $projectId;
    }

    public function startProcessing(): void
    {
        $this->performAction(ProjectWorkflowAction::Started);
    }

    public function advance(): void
    {
        $this->performAction(ProjectWorkflowAction::Advanced);
    }

    public function approve(): void
    {
        $this->performAction(ProjectWorkflowAction::Approved);
    }

    public function returnForCorrection(): void
    {
        $this->performAction(ProjectWorkflowAction::Returned, true);
    }

    public function reject(): void
    {
        $this->performAction(ProjectWorkflowAction::Rejected, true);
    }

    public function addNote(): void
    {
        $this->performAction(ProjectWorkflowAction::Note, true);
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::ProjectWorkflowView->value);

        $project = Project::query()
            ->with([
                'proponent',
                'projectType',
                'projectPurpose',
                'workflowState',
            ])
            ->findOrFail($this->projectId);

        $state = app(ProjectWorkflowService::class)
            ->ensureState($project);

        $actor = auth()->user();

        $availableActions = $actor
            ? app(ProjectWorkflowService::class)
                ->availableActions($state, $actor)
            : [];

        $events = $project->workflowEvents()
            ->with('actor')
            ->latest('acted_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.projects.workflow-details', [
            'project' => $project,
            'state' => $state,
            'events' => $events,
            'stages' => ProjectWorkflowStage::cases(),
            'availableActionValues' => array_map(
                fn (ProjectWorkflowAction $action): string => $action->value,
                $availableActions
            ),
        ]);
    }

    private function performAction(
        ProjectWorkflowAction $action,
        bool $remarksRequired = false,
    ): void {
        Gate::authorize(PermissionName::ProjectWorkflowUpdate->value);

        $rules = [
            'reference_number' => ['nullable', 'string', 'max:150'],
            'remarks' => [
                $remarksRequired ? 'required' : 'nullable',
                'string',
                'max:3000',
            ],
        ];

        $validated = $this->validate($rules);

        $project = Project::query()->findOrFail($this->projectId);

        app(ProjectWorkflowService::class)->act(
            $project,
            auth()->user(),
            $action,
            $validated['remarks'] ?? null,
            $validated['reference_number'] ?? null,
        );

        session()->flash(
            'workflow-status',
            $this->successMessage($action)
        );

        $this->reference_number = '';
        $this->remarks = '';
        $this->resetValidation();
        $this->resetPage();
    }

    private function successMessage(ProjectWorkflowAction $action): string
    {
        return match ($action) {
            ProjectWorkflowAction::Started => 'Workflow processing started.',
            ProjectWorkflowAction::Advanced => 'Project advanced to the next workflow stage.',
            ProjectWorkflowAction::Approved => 'Project approved and workflow completed.',
            ProjectWorkflowAction::Returned => 'Project returned for correction.',
            ProjectWorkflowAction::Rejected => 'Project workflow rejected.',
            ProjectWorkflowAction::Note => 'Workflow note recorded.',
            ProjectWorkflowAction::Registered => 'Project registered in workflow.',
        };
    }
}
