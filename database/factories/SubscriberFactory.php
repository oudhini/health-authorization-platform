<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subscriber> */
class SubscriberFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'insurer_id' => Organization::factory()->insurer(),
            'name' => fake()->unique()->company(),
        ];
    }
}
