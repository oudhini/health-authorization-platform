<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MedicalAct;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MedicalAct> */
class MedicalActFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('ACT-????-###'),
            'label' => fake()->unique()->words(3, true),
        ];
    }
}
