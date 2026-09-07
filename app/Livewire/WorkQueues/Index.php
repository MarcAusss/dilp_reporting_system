<?php

namespace App\Livewire\WorkQueues;

use App\Enums\PermissionName;
use App\Enums\ProjectWorkflowStage;
use App\Enums\ProjectWorkflowStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\ProjectWorkflowState;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $stageFilter = '';

    public string $statusFilter = 'active';

    public function mount(): void
    {
        Gate::authorize(PermissionName::WorkQueuesView->value);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStageFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::WorkQueuesView->value);

        $user = auth()->user();
        $roleValues = $user->getRoleNames()->all();
        $isSuperAdmin = $user->hasRole(UserRole::SuperAdmin->value);

        $stateScope = ProjectWorkflowState::query();

        if (! $isSuperAdmin) {
            $stateScope->whereIn('assigned_role', $roleValues);
        }

        $summary = [
            'active' => (clone $stateScope)
                ->whereNotIn('status', [
                    ProjectWorkflowStatus::Completed->value,
                    ProjectWorkflowStatus::Rejected->value,
                ])
                ->count(),
            'pending' => (clone $stateScope)
                ->where('status', ProjectWorkflowStatus::Pending->value)
                ->count(),
            'in_progress' => (clone $stateScope)
                ->where('status', ProjectWorkflowStatus::InProgress->value)
                ->count(),
            'returned' => (clone $stateScope)
                ->where('status', ProjectWorkflowStatus::Returned->value)
                ->count(),
        ];

        $projects = Project::query()
            ->with([
                'proponent',
                'office',
                'projectType',
                'workflowState',
            ])
            ->whereHas('workflowState', function (Builder $query) use (
                $roleValues,
                $isSuperAdmin,
            ): void {
                if (! $isSuperAdmin) {
                    $query->whereIn('assigned_role', $roleValues);
                }

                if (filled($this->stageFilter)) {
                    $query->where('stage', $this->stageFilter);
                }

                if ($this->statusFilter === 'active') {
                    $query->whereNotIn('status', [
                        ProjectWorkflowStatus::Completed->value,
                        ProjectWorkflowStatus::Rejected->value,
                    ]);
                } elseif (filled($this->statusFilter)) {
                    $query->where('status', $this->statusFilter);
                }
            })
            ->when(filled($this->search), function (Builder $query): void {
                $search = trim($this->search);

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('registry_number', 'like', "%{$search}%")
                        ->orWhere('project_code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhereHas(
                            'proponent',
                            fn (Builder $query) => $query->where(
                                'name',
                                'like',
                                "%{$search}%"
                            )
                        );
                });
            })
            ->orderByDesc(
                ProjectWorkflowState::query()
                    ->select('last_action_at')
                    ->whereColumn(
                        'project_workflow_states.project_id',
                        'projects.id'
                    )
                    ->limit(1)
            )
            ->paginate(15);

        return view('livewire.work-queues.index', [
            'projects' => $projects,
            'summary' => $summary,
            'stageOptions' => ProjectWorkflowStage::cases(),
            'statusOptions' => ProjectWorkflowStatus::cases(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}
