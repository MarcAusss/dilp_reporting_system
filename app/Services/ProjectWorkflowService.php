<?php

namespace App\Services;

use App\Enums\PermissionName;
use App\Enums\ProjectWorkflowAction;
use App\Enums\ProjectWorkflowStage;
use App\Enums\ProjectWorkflowStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\ProjectWorkflowState;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectWorkflowService
{
    public function ensureState(Project $project): ProjectWorkflowState
    {
        $state = $project->workflowState()->first();

        if ($state) {
            return $state;
        }

        return DB::transaction(function () use ($project): ProjectWorkflowState {
            $state = $project->workflowState()->firstOrCreate(
                ['project_id' => $project->id],
                [
                    'stage' => ProjectWorkflowStage::Evaluation,
                    'status' => ProjectWorkflowStatus::Pending,
                    'assigned_role' => UserRole::DilpCoordinator->value,
                    'started_at' => $project->created_at ?? now(),
                    'last_action_at' => $project->created_at ?? now(),
                    'updated_by' => $project->created_by,
                ]
            );

            if (! $project->workflowEvents()->exists()) {
                $project->workflowEvents()->create([
                    'from_stage' => null,
                    'from_status' => null,
                    'to_stage' => ProjectWorkflowStage::Evaluation,
                    'to_status' => ProjectWorkflowStatus::Pending,
                    'action' => ProjectWorkflowAction::Registered,
                    'assigned_role' => UserRole::DilpCoordinator->value,
                    'acted_by' => $project->created_by,
                    'acted_at' => $project->created_at ?? now(),
                ]);
            }

            return $state->refresh();
        });
    }

    public function canAct(ProjectWorkflowState $state, User $actor): bool
    {
        if ($actor->hasRole(UserRole::SuperAdmin->value)) {
            return true;
        }

        if (! $actor->can(PermissionName::ProjectWorkflowUpdate->value)) {
            return false;
        }

        return filled($state->assigned_role)
            && $actor->hasRole($state->assigned_role);
    }

    public function availableActions(ProjectWorkflowState $state, User $actor): array
    {
        if (! $this->canAct($state, $actor)) {
            return [];
        }

        if ($state->status->isTerminal()) {
            return [];
        }

        $actions = [ProjectWorkflowAction::Note];

        if (in_array($state->status, [
            ProjectWorkflowStatus::Pending,
            ProjectWorkflowStatus::Returned,
        ], true)) {
            $actions[] = ProjectWorkflowAction::Started;
        }

        if ($state->status === ProjectWorkflowStatus::InProgress) {
            if ($state->stage === ProjectWorkflowStage::Approval) {
                $actions[] = ProjectWorkflowAction::Approved;
            } elseif ($state->stage->next()) {
                $actions[] = ProjectWorkflowAction::Advanced;
            }
        }

        if ($state->status !== ProjectWorkflowStatus::Returned) {
            $actions[] = ProjectWorkflowAction::Returned;
        }

        $actions[] = ProjectWorkflowAction::Rejected;

        return $actions;
    }

    public function act(
        Project $project,
        User $actor,
        ProjectWorkflowAction $action,
        ?string $remarks = null,
        ?string $referenceNumber = null,
    ): ProjectWorkflowState {
        return DB::transaction(function () use (
            $project,
            $actor,
            $action,
            $remarks,
            $referenceNumber,
        ): ProjectWorkflowState {
            $this->ensureState($project);

            $state = ProjectWorkflowState::query()
                ->where('project_id', $project->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->canAct($state, $actor)) {
                throw new AuthorizationException(
                    'This workflow item is not assigned to your role.'
                );
            }

            if ($state->status->isTerminal()) {
                throw ValidationException::withMessages([
                    'workflow' => 'Completed or rejected workflows cannot be changed.',
                ]);
            }

            $remarks = $this->nullableTrim($remarks);
            $referenceNumber = $this->nullableTrim($referenceNumber);

            if (in_array($action, [
                ProjectWorkflowAction::Returned,
                ProjectWorkflowAction::Rejected,
                ProjectWorkflowAction::Note,
            ], true) && blank($remarks)) {
                throw ValidationException::withMessages([
                    'remarks' => 'Remarks are required for this workflow action.',
                ]);
            }

            $fromStage = $state->stage;
            $fromStatus = $state->status;
            $toStage = $fromStage;
            $toStatus = $fromStatus;
            $assignedRole = $state->assigned_role;

            switch ($action) {
                case ProjectWorkflowAction::Started:
                    $this->applyStart($state, $toStatus);
                    break;

                case ProjectWorkflowAction::Advanced:
                    $this->applyAdvance(
                        $state,
                        $toStage,
                        $toStatus,
                        $assignedRole,
                    );
                    break;

                case ProjectWorkflowAction::Approved:
                    $this->applyApproval(
                        $state,
                        $toStage,
                        $toStatus,
                        $assignedRole,
                    );
                    break;

                case ProjectWorkflowAction::Returned:
                    $this->applyReturn(
                        $toStage,
                        $toStatus,
                        $assignedRole,
                    );
                    break;

                case ProjectWorkflowAction::Rejected:
                    $this->applyRejection(
                        $toStatus,
                        $assignedRole,
                    );
                    break;

                case ProjectWorkflowAction::Note:
                    break;

                case ProjectWorkflowAction::Registered:
                    throw ValidationException::withMessages([
                        'workflow' => 'Registration is a system-generated workflow event.',
                    ]);
            }

            $now = now();

            $state->update([
                'stage' => $toStage,
                'status' => $toStatus,
                'assigned_role' => $assignedRole,
                'started_at' => $toStage !== $fromStage
                    ? $now
                    : $state->started_at,
                'last_action_at' => $now,
                'updated_by' => $actor->id,
            ]);

            $project->workflowEvents()->create([
                'from_stage' => $fromStage,
                'from_status' => $fromStatus,
                'to_stage' => $toStage,
                'to_status' => $toStatus,
                'action' => $action,
                'assigned_role' => $assignedRole,
                'reference_number' => $referenceNumber,
                'remarks' => $remarks,
                'acted_by' => $actor->id,
                'acted_at' => $now,
            ]);

            return $state->refresh();
        });
    }

    private function applyStart(
        ProjectWorkflowState $state,
        ProjectWorkflowStatus &$toStatus,
    ): void {
        if (! in_array($state->status, [
            ProjectWorkflowStatus::Pending,
            ProjectWorkflowStatus::Returned,
        ], true)) {
            throw ValidationException::withMessages([
                'workflow' => 'Only pending or returned work can be started.',
            ]);
        }

        $toStatus = ProjectWorkflowStatus::InProgress;
    }

    private function applyAdvance(
        ProjectWorkflowState $state,
        ProjectWorkflowStage &$toStage,
        ProjectWorkflowStatus &$toStatus,
        ?string &$assignedRole,
    ): void {
        if ($state->status !== ProjectWorkflowStatus::InProgress) {
            throw ValidationException::withMessages([
                'workflow' => 'Start processing before advancing this project.',
            ]);
        }

        $nextStage = $state->stage->next();

        if (! $nextStage || $state->stage === ProjectWorkflowStage::Approval) {
            throw ValidationException::withMessages([
                'workflow' => 'This stage cannot be advanced using this action.',
            ]);
        }

        $toStage = $nextStage;
        $toStatus = ProjectWorkflowStatus::Pending;
        $assignedRole = $nextStage->assignedRole()?->value;
    }

    private function applyApproval(
        ProjectWorkflowState $state,
        ProjectWorkflowStage &$toStage,
        ProjectWorkflowStatus &$toStatus,
        ?string &$assignedRole,
    ): void {
        if (
            $state->stage !== ProjectWorkflowStage::Approval
            || $state->status !== ProjectWorkflowStatus::InProgress
        ) {
            throw ValidationException::withMessages([
                'workflow' => 'The project must be in active approval processing before it can be approved.',
            ]);
        }

        $toStage = ProjectWorkflowStage::Completed;
        $toStatus = ProjectWorkflowStatus::Completed;
        $assignedRole = null;
    }

    private function applyReturn(
        ProjectWorkflowStage &$toStage,
        ProjectWorkflowStatus &$toStatus,
        ?string &$assignedRole,
    ): void {
        $toStage = ProjectWorkflowStage::Evaluation;
        $toStatus = ProjectWorkflowStatus::Returned;
        $assignedRole = UserRole::DilpCoordinator->value;
    }

    private function applyRejection(
        ProjectWorkflowStatus &$toStatus,
        ?string &$assignedRole,
    ): void {
        $toStatus = ProjectWorkflowStatus::Rejected;
        $assignedRole = null;
    }

    private function nullableTrim(?string $value): ?string
    {
        return filled($value) ? trim($value) : null;
    }
}
