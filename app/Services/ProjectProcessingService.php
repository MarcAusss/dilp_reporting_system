<?php

namespace App\Services;

use App\Enums\DisbursementStatus;
use App\Enums\ObligationStatus;
use App\Enums\ProjectWorkflowStage;
use App\Enums\ProjectWorkflowStatus;
use App\Models\Project;
use Illuminate\Validation\ValidationException;

class ProjectProcessingService
{
    public function isUnlocked(Project $project): bool
    {
        $state = $project->workflowState;

        return $state
            && $state->stage === ProjectWorkflowStage::Completed
            && $state->status === ProjectWorkflowStatus::Completed;
    }

    public function assertUnlocked(Project $project): void
    {
        if ($this->isUnlocked($project)) {
            return;
        }

        throw ValidationException::withMessages([
            'processing' => 'Financial and implementation processing is available only after the project workflow has been approved and completed.',
        ]);
    }

    public function summary(Project $project): array
    {
        $project->loadMissing([
            'financial',
            'procurements',
            'obligations',
            'disbursements',
            'insurances',
            'implementation',
            'replacementRequests',
        ]);

        $doleShare = round((float) ($project->financial?->doleShare() ?? 0), 2);

        $procurementTotal = round((float) $project->procurements
            ->reject(fn ($record): bool => $record->status->value === 'cancelled')
            ->sum(fn ($record): float => (float) $record->amount), 2);

        $obligated = round((float) $project->obligations
            ->filter(fn ($record): bool => $record->status === ObligationStatus::Obligated)
            ->sum(fn ($record): float => (float) $record->amount), 2);

        $disbursed = round((float) $project->disbursements
            ->filter(fn ($record): bool => $record->status === DisbursementStatus::Paid)
            ->sum(fn ($record): float => (float) $record->amount), 2);

        $insurancePremium = round((float) $project->insurances
            ->reject(fn ($record): bool => $record->status->value === 'cancelled')
            ->sum(fn ($record): float => (float) $record->premium_amount), 2);

        return [
            'dole_share' => $doleShare,
            'procurement_total' => $procurementTotal,
            'obligated' => $obligated,
            'disbursed' => $disbursed,
            'remaining_unobligated' => round($doleShare - $obligated, 2),
            'undisbursed_obligation' => round($obligated - $disbursed, 2),
            'utilization_percentage' => $doleShare > 0
                ? round(($disbursed / $doleShare) * 100, 2)
                : 0,
            'insurance_premium' => $insurancePremium,
            'replacement_total' => round((float) $project->replacementRequests
                ->filter(fn ($record): bool => in_array(
                    $record->status,
                    [
                        \App\Enums\ReplacementRequestStatus::Approved,
                        \App\Enums\ReplacementRequestStatus::Completed,
                    ],
                    true
                ))
                ->sum(fn ($record): float => (float) $record->requested_amount), 2),
        ];
    }
}
