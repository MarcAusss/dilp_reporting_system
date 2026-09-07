<?php

namespace App\Enums;

enum ProjectWorkflowStage: string
{
    case Evaluation = 'evaluation';
    case Endorsement = 'endorsement';
    case Validation = 'validation';
    case Approval = 'approval';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Evaluation => 'Evaluation',
            self::Validation => 'Validation',
            self::Endorsement => 'Endorsement',
            self::Approval => 'Approval',
            self::Completed => 'Completed',
        };
    }

    public function assignedRole(): ?UserRole
    {
        return match ($this) {
            self::Evaluation,
            self::Endorsement => UserRole::DilpCoordinator,
            self::Validation => UserRole::Focal,
            self::Approval => UserRole::SuperAdmin,
            self::Completed => null,
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::Evaluation => self::Endorsement,
            self::Endorsement => self::Validation,
            self::Validation => self::Approval,
            self::Approval,
            self::Completed => null,
        };
    }
}
