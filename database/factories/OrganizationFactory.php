<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Organization> */
class OrganizationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'type' => OrganizationType::Facility,
        ];
    }

    public function facility(): static
    {
        return $this->state(fn (): array => ['type' => OrganizationType::Facility]);
    }

    public function insurer(): static
    {
        return $this->state(fn (): array => ['type' => OrganizationType::Insurer]);
    }
}
