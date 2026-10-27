<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Domain\Requests\RequestStatus;
use App\Enums\OrganizationType;
use App\Enums\UserRole;
use App\Models\Authorization;
use App\Models\AuthorizationRequest;
use App\Models\MedicalAct;
use App\Models\OfficialTariff;
use App\Models\Policy;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Scenario;
use Tests\TestCase;

final class ModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_roles_and_organizations_are_cast_to_enums(): void
    {
        $s = Scenario::create();

        $this->assertSame(UserRole::FacilityAgent, $s->agent->fresh()->role);
        $this->assertSame(UserRole::PlatformAdmin, $s->admin->fresh()->role);
        $this->assertNull($s->admin->organization_id);
        $this->assertSame(OrganizationType::Facility, $s->agent->organization->type);
        $this->assertSame(OrganizationType::Insurer, $s->manager->organization->type);
    }

    public function test_each_role_requires_the_matching_organization_type(): void
    {
        $this->assertSame(OrganizationType::Facility, UserRole::FacilityAgent->requiredOrganizationType());
        $this->assertSame(OrganizationType::Insurer, UserRole::InsurerManager->requiredOrganizationType());
        $this->assertNull(UserRole::PlatformAdmin->requiredOrganizationType());
    }

    public function test_role_and_organization_cannot_be_mass_assigned(): void
    {
        $s = Scenario::create();

        $s->agent->fill(['role' => UserRole::PlatformAdmin->value, 'organization_id' => null]);

        $this->assertSame(UserRole::FacilityAgent, $s->agent->role);
        $this->assertSame($s->facility->id, $s->agent->organization_id);
    }

    public function test_beneficiary_is_linked_to_its_insurer_through_the_chain(): void
    {
        $s = Scenario::create();

        $this->assertSame($s->insurer->id, $s->beneficiary->policyholder->subscriber->insurer_id);
        $this->assertSame($s->insurer->id, $s->policy->insurer_id);
    }

    public function test_policy_is_active_on_both_boundary_days_and_not_outside(): void
    {
        $s = Scenario::create();
        $s->policy->update(['starts_on' => '2026-03-01', 'ends_on' => '2026-03-31']);

        $this->assertTrue(Policy::query()->activeOn(CarbonImmutable::parse('2026-03-01'))->exists());
        $this->assertTrue(Policy::query()->activeOn(CarbonImmutable::parse('2026-03-31'))->exists());
        $this->assertFalse(Policy::query()->activeOn(CarbonImmutable::parse('2026-02-28'))->exists());
        $this->assertFalse(Policy::query()->activeOn(CarbonImmutable::parse('2026-04-01'))->exists());

        $fresh = $s->policy->fresh();
        $this->assertTrue($fresh->isActiveOn(CarbonImmutable::parse('2026-03-31')));
        $this->assertFalse($fresh->isActiveOn(CarbonImmutable::parse('2026-04-01')));
    }

    public function test_beneficiary_active_policy_ignores_expired_ones(): void
    {
        $s = Scenario::create();
        $s->policy->update(['starts_on' => '2020-01-01', 'ends_on' => '2020-12-31']);

        $this->assertNull($s->beneficiary->fresh()->activePolicy(CarbonImmutable::today()));
    }

    public function test_official_tariff_lookup_returns_the_latest_tariff_in_force(): void
    {
        $act = MedicalAct::factory()->create();
        OfficialTariff::factory()->create(['medical_act_id' => $act->id, 'amount' => 1000, 'effective_from' => '2026-01-01']);
        OfficialTariff::factory()->create(['medical_act_id' => $act->id, 'amount' => 1200, 'effective_from' => '2026-07-01']);

        $this->assertNull(OfficialTariff::amountFor($act->id, CarbonImmutable::parse('2025-12-31')));
        $this->assertSame(1000, OfficialTariff::amountFor($act->id, CarbonImmutable::parse('2026-06-30')));
        $this->assertSame(1200, OfficialTariff::amountFor($act->id, CarbonImmutable::parse('2026-07-01')));
    }

    public function test_request_status_is_cast_and_totals_are_summed_from_items(): void
    {
        $s = Scenario::create();

        $request = AuthorizationRequest::create([
            'facility_id' => $s->facility->id,
            'insurer_id' => $s->insurer->id,
            'beneficiary_id' => $s->beneficiary->id,
            'policy_id' => $s->policy->id,
            'status' => RequestStatus::Draft,
            'coverage_rate' => 80,
            'created_by' => $s->agent->id,
        ]);
        $request->items()->create([
            'medical_act_id' => $s->echo->id, 'billed_amount' => 12000, 'official_tariff' => 10000,
            'accepted_amount' => 10000, 'insurer_share' => 8000, 'patient_share' => 4000, 'overrun' => 2000,
        ]);
        $request->items()->create([
            'medical_act_id' => $s->consultation->id, 'billed_amount' => 5000, 'official_tariff' => 5000,
            'accepted_amount' => 5000, 'insurer_share' => 4000, 'patient_share' => 1000, 'overrun' => 0,
        ]);

        $request = $request->fresh(['items']);
        $totals = $request->coverageTotals();

        $this->assertSame(RequestStatus::Draft, $request->status);
        $this->assertTrue($request->isEditable());
        $this->assertSame(17000, $totals->billedAmount);
        $this->assertSame(12000, $totals->insurerShare);
        $this->assertSame(5000, $totals->patientShare);
        $this->assertSame(2000, $totals->overrun);
    }

    public function test_exam_requests_require_a_medical_form(): void
    {
        $s = Scenario::create();
        $base = [
            'facility_id' => $s->facility->id, 'insurer_id' => $s->insurer->id,
            'beneficiary_id' => $s->beneficiary->id, 'policy_id' => $s->policy->id,
            'status' => RequestStatus::Draft, 'coverage_rate' => 100, 'created_by' => $s->agent->id,
        ];

        $consultation = AuthorizationRequest::create($base);
        $exam = AuthorizationRequest::create($base + ['related_request_id' => $consultation->id]);

        $this->assertSame([], $consultation->requiredDocumentTypes());
        $this->assertCount(1, $exam->missingDocumentTypes());
    }

    public function test_authorization_number_is_derived_from_its_id_and_validity_is_computed(): void
    {
        $s = Scenario::create();
        $request = AuthorizationRequest::create([
            'facility_id' => $s->facility->id, 'insurer_id' => $s->insurer->id,
            'beneficiary_id' => $s->beneficiary->id, 'policy_id' => $s->policy->id,
            'status' => RequestStatus::Approved, 'coverage_rate' => 100, 'created_by' => $s->agent->id,
        ]);
        $authorization = Authorization::create([
            'authorization_request_id' => $request->id,
            'valid_from' => '2026-10-01', 'valid_until' => '2026-10-08',
            'billed_total' => 10000, 'insurer_total' => 10000, 'patient_total' => 0, 'overrun_total' => 0,
            'decided_by' => $s->manager->id,
        ])->fresh();

        $this->assertSame(sprintf('BPC-%06d', $authorization->id), $authorization->number);
        $this->assertSame('not_started', $authorization->validityStatus(CarbonImmutable::parse('2026-09-30')));
        $this->assertSame('active', $authorization->validityStatus(CarbonImmutable::parse('2026-10-08')));
        $this->assertSame('expired', $authorization->validityStatus(CarbonImmutable::parse('2026-10-09')));
    }
}
