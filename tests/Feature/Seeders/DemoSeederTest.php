<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Enums\UserRole;
use App\Enums\OrganizationType;
use App\Models\Beneficiary;
use App\Models\OfficialTariff;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_a_usable_demo(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, User::query()->where('role', UserRole::FacilityAgent->value)->count());
        $this->assertSame(2, User::query()->where('role', UserRole::InsurerManager->value)->count());
        $this->assertSame(1, User::query()->where('role', UserRole::PlatformAdmin->value)->count());

        $beneficiary = Beneficiary::query()->where('member_number', 'DEMO-ALPHA-0002')->firstOrFail();
        $policies = Policy::query()
            ->where('subscriber_id', $beneficiary->policyholder->subscriber_id)
            ->activeOn(now())
            ->get();

        $this->assertCount(1, $policies);
        $policy = $policies->firstOrFail();
        $this->assertSame(100, $policy->coverage_rate);
        $this->assertSame(6, OfficialTariff::query()->count());
        $facility = Organization::query()->where('type', OrganizationType::Facility)->firstOrFail();
        $this->assertCount(2, $facility->partnerInsurers);
    }

    public function test_every_agent_and_manager_belongs_to_an_organization_of_the_right_type(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (User::query()->with('organization')->get() as $user) {
            $expected = $user->role->requiredOrganizationType();

            $this->assertSame($expected, $user->organization?->type, $user->email);
        }
    }

    public function test_demo_accounts_can_log_in_with_the_local_demo_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'agent.a@demo.test')->firstOrFail();

        $this->assertTrue(password_verify('password', $user->password));
    }
}
