<?php

declare(strict_types=1);

namespace App\Enums;

enum OrganizationType: string
{
    case Facility = 'facility';
    case Insurer = 'insurer';

    public function label(): string
    {
        return match ($this) {
            self::Facility => 'Établissement de santé',
            self::Insurer => 'Assureur',
        };
    }
}
