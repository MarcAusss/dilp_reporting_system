<?php

namespace App\Enums;

enum ProponentType: string
{
    case Individual = 'individual';
    case Group = 'group';
    case Organization = 'organization';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Individual',
            self::Group => 'Group',
            self::Organization => 'Organization',
        };
    }
}