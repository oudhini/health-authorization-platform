<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Beneficiary;
use App\Models\Policyholder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Beneficiary> */
class BeneficiaryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'policyholder_id' => Policyholder::factory(),
            'insurer_id' => fn (array $attributes): int => (int) Policyholder::query()
                ->with('subscriber')
                ->findOrFail($attributes['policyholder_id'])
                ->subscriber
                ->insurer_id,
            'name' => fake()->name(),
            'birth_date' => fake()->date(),
            'relationship' => 'child',
            'member_number' => fake()->unique()->numerify('MBR-######'),
        ];
    }
}
