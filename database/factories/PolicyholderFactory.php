<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Policyholder;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Policyholder> */
class PolicyholderFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'subscriber_id' => Subscriber::factory(),
            'name' => fake()->name(),
        ];
    }
}
