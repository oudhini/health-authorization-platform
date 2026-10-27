<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Database\Seeder;

final class FacilityInsurerSeeder extends Seeder
{
    public function run(): void
    {
        $insurers = Organization::query()
            ->where('type', OrganizationType::Insurer)
            ->whereIn('name', ['Assurance Démo Alpha', 'Assurance Démo Beta'])
            ->get()
            ->keyBy('name');

        foreach (['Hôpital Démo A', 'Clinique Démo B'] as $facilityName) {
            $facility = Organization::query()
                ->where('type', OrganizationType::Facility)
                ->where('name', $facilityName)
                ->firstOrFail();

            $facility->partnerInsurers()->syncWithoutDetaching($insurers->modelKeys());
        }
    }
}
