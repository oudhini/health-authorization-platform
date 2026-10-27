<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Policy;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Policy> */
class PolicyFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numerify('POL-######'),
            'subscriber_id' => Subscriber::factory(),
            // A policy always belongs to the insurer of its subscriber.
            'insurer_id' => fn (array $attributes): int => (int) Subscriber::query()
                ->findOrFail($attributes['subscriber_id'])
                ->insurer_id,
            'coverage_rate' => 100,
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
        ];
    }

    public function rate(int $percent): static
    {
        return $this->state(fn (): array => ['coverage_rate' => $percent]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'starts_on' => now()->subYear()->toDateString(),
            'ends_on' => now()->subDay()->toDateString(),
        ]);
    }
}
