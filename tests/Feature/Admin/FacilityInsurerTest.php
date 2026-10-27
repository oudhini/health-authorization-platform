<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\OrganizationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Scenario;
use Tests\TestCase;

final class FacilityInsurerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_a_facilitys_partner_insurers(): void
    {
        $scenario = Scenario::create();

        $this->actingAs($scenario->admin)
            ->get(route('admin.facility-insurers.index'))
            ->assertOk()
            ->assertSee($scenario->facility->name)
            ->assertSee($scenario->insurer->name)
            ->assertSee($scenario->otherInsurer->name);

        $this->put(route('admin.facility-insurers.update', $scenario->facility), [
            'insurer_ids' => [$scenario->otherInsurer->id],
        ])
            ->assertRedirect(route('admin.facility-insurers.index'))
            ->assertSessionHasNoErrors();

        $this->assertFalse($scenario->facility->partnerInsurers()->whereKey($scenario->insurer->id)->exists());
        $this->assertTrue($scenario->facility->partnerInsurers()->whereKey($scenario->otherInsurer->id)->exists());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'facility.insurers_updated',
            'user_id' => $scenario->admin->id,
            'subject_id' => $scenario->facility->id,
        ]);
    }

    public function test_only_platform_admins_can_manage_partner_insurers(): void
    {
        $scenario = Scenario::create();

        foreach ([$scenario->agent, $scenario->manager] as $user) {
            $this->actingAs($user)
                ->get(route('admin.facility-insurers.index'))
                ->assertForbidden();
            $this->put(route('admin.facility-insurers.update', $scenario->facility), [
                'insurer_ids' => [$scenario->otherInsurer->id],
            ])->assertForbidden();
        }
    }

    public function test_a_non_insurer_organization_cannot_be_added_as_a_partner(): void
    {
        $scenario = Scenario::create();
        $anotherFacility = \App\Models\Organization::factory()->facility()->create();

        $this->actingAs($scenario->admin)
            ->put(route('admin.facility-insurers.update', $scenario->facility), [
                'insurer_ids' => [$anotherFacility->id],
            ])
            ->assertSessionHasErrors('insurer_ids');

        $this->assertDatabaseMissing('facility_insurer', [
            'facility_id' => $scenario->facility->id,
            'insurer_id' => $anotherFacility->id,
        ]);
    }

    public function test_non_facility_organizations_cannot_be_updated_as_facilities(): void
    {
        $scenario = Scenario::create();

        $this->actingAs($scenario->admin)
            ->put(route('admin.facility-insurers.update', $scenario->insurer), [
                'insurer_ids' => [],
            ])
            ->assertNotFound();
    }
}
