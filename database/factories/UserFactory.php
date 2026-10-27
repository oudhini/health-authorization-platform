<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /** The current password being used by the factory. */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory()->facility(),
            'role' => UserRole::FacilityAgent,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function facilityAgent(?Organization $organization = null): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::FacilityAgent,
            'organization_id' => $organization?->id ?? Organization::factory()->facility(),
        ]);
    }

    public function insurerManager(?Organization $organization = null): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::InsurerManager,
            'organization_id' => $organization?->id ?? Organization::factory()->insurer(),
        ]);
    }

    public function platformAdmin(): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::PlatformAdmin,
            'organization_id' => null,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
