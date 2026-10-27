<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case FacilityAgent = 'facility_agent';
    case InsurerManager = 'insurer_manager';
    case PlatformAdmin = 'platform_admin';

    public function label(): string
    {
        return match ($this) {
            self::FacilityAgent => 'Agent d\'établissement',
            self::InsurerManager => 'Gestionnaire assureur',
            self::PlatformAdmin => 'Administrateur',
        };
    }

    /**
     * Type of organization a user with this role must belong to.
     * The platform administrator belongs to no organization.
     */
    public function requiredOrganizationType(): ?OrganizationType
    {
        return match ($this) {
            self::FacilityAgent => OrganizationType::Facility,
            self::InsurerManager => OrganizationType::Insurer,
            self::PlatformAdmin => null,
        };
    }
}
