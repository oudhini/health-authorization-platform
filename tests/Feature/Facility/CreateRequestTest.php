<?php

declare(strict_types=1);

namespace Tests\Feature\Facility;

use App\Domain\Requests\RequestStatus;
use App\Models\AuthorizationItem;
use App\Models\AuthorizationRequest;
use App\Models\Beneficiary;
use App\Models\MedicalAct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Scenario;
use Tests\TestCase;

/** Workflow 1, steps 1 to 4 (docs/requirements.md, section 7.1). */
final class CreateRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @param list<array{medical_act_id: int|string, billed_amount: int|string}> $acts */
    private function payload(Scenario $s, array $acts, array $extra = []): array
    {
        return array_replace([
            'insurer_id' => $s->insurer->id,
            'coverage_selection' => $s->beneficiary->id.':'.$s->policy->id,
            'acts' => $acts,
        ], $extra);
    }

    public function test_agent_creates_a_draft_priced_from_the_official_tariff_and_the_policy_rate(): void
    {
        $s = Scenario::create(coverageRate: 80);

        $response = $this->actingAs($s->agent)->post(
            route('facility.requests.store'),
            $this->payload($s, [['medical_act_id' => $s->echo->id, 'billed_amount' => 12000]]),
        );

        $created = AuthorizationRequest::query()->firstOrFail();
        $response->assertRedirect(route('facility.requests.show', $created));

        $this->assertSame(RequestStatus::Draft, $created->status);
        $this->assertSame(80, $created->coverage_rate);
        $this->assertSame($s->facility->id, $created->facility_id);
        $this->assertSame($s->insurer->id, $created->insurer_id);
        $this->assertSame($s->agent->id, $created->created_by);

        // Case E of section 6.4: 80%, billed 12000 against an official tariff of 10000.
        $this->assertDatabaseHas('authorization_items', [
            'authorization_request_id' => $created->id,
            'medical_act_id' => $s->echo->id,
            'billed_amount' => 12000,
            'official_tariff' => 10000,
            'accepted_amount' => 10000,
            'insurer_share' => 8000,
            'patient_share' => 4000,
            'overrun' => 2000,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'request.created', 'user_id' => $s->agent->id]);
    }

    public function test_each_act_is_priced_on_its_own(): void
    {
        $s = Scenario::create(coverageRate: 80);

        $this->actingAs($s->agent)->post(route('facility.requests.store'), $this->payload($s, [
            ['medical_act_id' => $s->echo->id, 'billed_amount' => 12000],
            ['medical_act_id' => $s->consultation->id, 'billed_amount' => 4000], // cheaper than its tariff
        ]));

        $created = AuthorizationRequest::query()->firstOrFail()->load('items');
        $totals = $created->coverageTotals();

        $this->assertCount(2, $created->items);
        $this->assertSame(16000, $totals->billedAmount);
        $this->assertSame(11200, $totals->insurerShare); // 8000 + 3200
        $this->assertSame(4800, $totals->patientShare);  // 4000 + 800
        $this->assertSame(2000, $totals->overrun);
    }

    public function test_insurer_rate_is_applied_to_the_facility_price_when_below_tariff(): void
    {
        $s = Scenario::create(coverageRate: 80);

        $this->actingAs($s->agent)->post(route('facility.requests.store'), $this->payload($s, [
            ['medical_act_id' => $s->echo->id, 'billed_amount' => 8000],
        ]))->assertSessionHasNoErrors();

        $request = AuthorizationRequest::query()->firstOrFail();

        $this->assertDatabaseHas('authorization_items', [
            'authorization_request_id' => $request->id,
            'billed_amount' => 8000,
            'official_tariff' => 10000,
            'accepted_amount' => 8000,
            'insurer_share' => 6400,
            'patient_share' => 1600,
            'overrun' => 0,
        ]);
    }

    public function test_empty_rows_of_the_form_are_ignored(): void
    {
        $s = Scenario::create();

        $this->actingAs($s->agent)->post(route('facility.requests.store'), $this->payload($s, [
            ['medical_act_id' => $s->echo->id, 'billed_amount' => 10000],
            ['medical_act_id' => '', 'billed_amount' => ''],
            ['medical_act_id' => '', 'billed_amount' => ''],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, AuthorizationItem::query()->count());
    }

    public function test_facility_and_status_cannot_be_chosen_by_the_user(): void
    {
        $s = Scenario::create();

        $this->actingAs($s->agent)->post(route('facility.requests.store'), $this->payload(
            $s,
            [['medical_act_id' => $s->echo->id, 'billed_amount' => 10000]],
            ['facility_id' => $s->otherFacility->id, 'status' => 'approved'],
        ));

        $created = AuthorizationRequest::query()->firstOrFail();

        $this->assertSame($s->facility->id, $created->facility_id);
        $this->assertSame($s->insurer->id, $created->insurer_id);
        $this->assertSame(RequestStatus::Draft, $created->status);
    }

    public function test_only_a_partner_insurer_can_be_selected(): void
    {
        $s = Scenario::create();

        $this->actingAs($s->agent)->post(route('facility.requests.store'), $this->payload(
            $s,
            [['medical_act_id' => $s->echo->id, 'billed_amount' => 10000]],
            ['insurer_id' => $s->otherInsurer->id],
        ))->assertSessionHasErrors('request');

        $this->assertSame(0, AuthorizationRequest::query()->count());
    }

    public function test_the_selected_policy_controls_the_coverage_rate(): void
    {
        $s = Scenario::create(coverageRate: 80);
        $secondPolicy = \App\Models\Policy::factory()->rate(65)->create([
            'subscriber_id' => $s->subscriber->id,
            'insurer_id' => $s->insurer->id,
            'number' => 'POL-SECOND-65',
        ]);

        $this->actingAs($s->agent)->post(route('facility.requests.store'), $this->payload(
            $s,
            [['medical_act_id' => $s->echo->id, 'billed_amount' => 10000]],
            ['coverage_selection' => $s->beneficiary->id.':'.$secondPolicy->id],
        ))->assertSessionHasNoErrors();

        $created = AuthorizationRequest::query()->firstOrFail();
        $this->assertSame($secondPolicy->id, $created->policy_id);
        $this->assertSame(65, $created->coverage_rate);
        $this->assertDatabaseHas('authorization_items', [
            'authorization_request_id' => $created->id,
            'insurer_share' => 6500,
            'patient_share' => 3500,
        ]);
    }

    public function test_a_beneficiary_cannot_be_paired_with_another_subscribers_policy(): void
    {
        $s = Scenario::create();
        $otherPolicy = \App\Models\Policy::factory()->create([
            'subscriber_id' => \App\Models\Subscriber::factory()->create([
                'insurer_id' => $s->insurer->id,
            ])->id,
            'insurer_id' => $s->insurer->id,
        ]);

        $this->actingAs($s->agent)->post(route('facility.requests.store'), $this->payload(
            $s,
            [['medical_act_id' => $s->echo->id, 'billed_amount' => 10000]],
            ['coverage_selection' => $s->beneficiary->id.':'.$otherPolicy->id],
        ))->assertSessionHasErrors('request');

        $this->assertSame(0, AuthorizationRequest::query()->count());
    }

    public function test_unknown_beneficiary_selection_is_refused(): void
    {
        $s = Scenario::create();

        $this->actingAs($s->agent)->post(route('facility.requests.store'), [
            'insurer_id' => $s->insurer->id,
            'coverage_selection' => '999999:'.$s->policy->id,
            'acts' => [['medical_act_id' => $s->echo->id, 'billed_amount' => 10000]],
        ])->assertSessionHasErrors('request');

        $this->assertSame(0, AuthorizationRequest::query()->count());
    }

    public function test_beneficiary_without_an_active_policy_is_refused(): void
    {
        $s = Scenario::create();
        $s->policy->update(['starts_on' => '2020-01-01', 'ends_on' => '2020-12-31']);

        $this->actingAs($s->agent)->post(
            route('facility.requests.store'),
            $this->payload($s, [['medical_act_id' => $s->echo->id, 'billed_amount' => 10000]]),
        )->assertSessionHasErrors('request');

        $this->assertSame(0, AuthorizationRequest::query()->count());
    }

    public function test_act_without_an_official_tariff_is_refused_and_nothing_is_saved(): void
    {
        $s = Scenario::create();
        $unpriced = MedicalAct::factory()->create(['label' => 'Acte sans tarif']);

        $this->actingAs($s->agent)->post(route('facility.requests.store'), $this->payload($s, [
            ['medical_act_id' => $s->echo->id, 'billed_amount' => 10000],
            ['medical_act_id' => $unpriced->id, 'billed_amount' => 5000],
        ]))->assertSessionHasErrors('request');

        // The whole creation is one transaction: the first act must not remain.
        $this->assertSame(0, AuthorizationRequest::query()->count());
        $this->assertSame(0, AuthorizationItem::query()->count());
    }

    public function test_form_validation(): void
    {
        $s = Scenario::create();
        $this->actingAs($s->agent);

        $this->post(route('facility.requests.store'), [
            'insurer_id' => $s->insurer->id,
            'coverage_selection' => $s->beneficiary->id.':'.$s->policy->id,
        ])
            ->assertSessionHasErrors('acts');

        $this->post(route('facility.requests.store'), $this->payload($s, [
            ['medical_act_id' => $s->echo->id, 'billed_amount' => -5],
        ]))->assertSessionHasErrors('acts.0.billed_amount');

        $this->post(route('facility.requests.store'), $this->payload($s, [
            ['medical_act_id' => $s->echo->id, 'billed_amount' => 0],
        ]))->assertSessionHasErrors('acts.0.billed_amount');

        $this->post(route('facility.requests.store'), $this->payload($s, [
            ['medical_act_id' => $s->echo->id, 'billed_amount' => '12.5'],
        ]))->assertSessionHasErrors('acts.0.billed_amount');

        $this->post(route('facility.requests.store'), $this->payload($s, [
            ['medical_act_id' => 999999, 'billed_amount' => 1000],
        ]))->assertSessionHasErrors('acts.0.medical_act_id');

        $this->post(route('facility.requests.store'), ['acts' => [['medical_act_id' => $s->echo->id, 'billed_amount' => 1000]]])
            ->assertSessionHasErrors(['insurer_id', 'coverage_selection']);

        $this->assertSame(0, AuthorizationRequest::query()->count());
    }

    public function test_related_request_must_be_an_approved_request_of_the_same_facility_and_beneficiary(): void
    {
        $s = Scenario::create();
        $act = [['medical_act_id' => $s->echo->id, 'billed_amount' => 10000]];

        $this->actingAs($s->agent)->post(route('facility.requests.store'), $this->payload($s, $act));
        $consultation = AuthorizationRequest::query()->firstOrFail();

        // Still a draft: not acceptable as the origin of an exam request.
        $this->post(route('facility.requests.store'), $this->payload($s, $act, ['related_request_id' => $consultation->id]))
            ->assertSessionHasErrors('request');
        $this->assertSame(1, AuthorizationRequest::query()->count());

        $consultation->update(['status' => RequestStatus::Approved]);

        $this->post(route('facility.requests.store'), $this->payload($s, $act, ['related_request_id' => $consultation->id]))
            ->assertSessionHasNoErrors();

        $exam = AuthorizationRequest::query()->latest('id')->firstOrFail();
        $this->assertSame($consultation->id, $exam->related_request_id);
    }

    public function test_related_request_for_another_beneficiary_cannot_be_linked(): void
    {
        $s = Scenario::create();
        $act = [['medical_act_id' => $s->echo->id, 'billed_amount' => 10000]];

        $this->actingAs($s->agent)->post(route('facility.requests.store'), $this->payload($s, $act));
        $consultation = AuthorizationRequest::query()->firstOrFail();
        $consultation->update(['status' => RequestStatus::Approved]);

        $otherBeneficiary = Beneficiary::factory()->create([
            'policyholder_id' => $s->beneficiary->policyholder_id,
        ]);

        $this->post(route('facility.requests.store'), $this->payload($s, $act, [
            'coverage_selection' => $otherBeneficiary->id.':'.$s->policy->id,
            'related_request_id' => $consultation->id,
        ]))->assertSessionHasErrors('request');

        $this->assertSame(1, AuthorizationRequest::query()->count());
    }

    public function test_another_facility_cannot_link_to_this_facilitys_request(): void
    {
        $s = Scenario::create();
        $act = [['medical_act_id' => $s->echo->id, 'billed_amount' => 10000]];

        $this->actingAs($s->agent)->post(route('facility.requests.store'), $this->payload($s, $act));
        $consultation = AuthorizationRequest::query()->firstOrFail();
        $consultation->update(['status' => RequestStatus::Approved]);

        $this->actingAs($s->otherAgent)
            ->post(route('facility.requests.store'), $this->payload($s, $act, ['related_request_id' => $consultation->id]))
            ->assertSessionHasErrors('request');
    }

    public function test_related_request_from_another_insurer_cannot_be_linked(): void
    {
        $s = Scenario::create();
        $act = [['medical_act_id' => $s->echo->id, 'billed_amount' => 10000]];

        $this->actingAs($s->agent)->post(route('facility.requests.store'), $this->payload($s, $act));
        $related = AuthorizationRequest::query()->firstOrFail();
        $related->update([
            'status' => RequestStatus::Approved,
            'insurer_id' => $s->otherInsurer->id,
        ]);

        $this->post(route('facility.requests.store'), $this->payload($s, $act, [
            'related_request_id' => $related->id,
        ]))->assertSessionHasErrors('request');

        $this->assertSame(1, AuthorizationRequest::query()->count());
    }

    public function test_beneficiary_from_another_insurer_cannot_be_submitted_with_the_selected_insurers_policy(): void
    {
        $s = Scenario::create();
        $s->facility->partnerInsurers()->attach($s->otherInsurer);
        $otherSubscriber = \App\Models\Subscriber::factory()->create(['insurer_id' => $s->otherInsurer->id]);
        $otherHolder = \App\Models\Policyholder::factory()->create(['subscriber_id' => $otherSubscriber->id]);
        $otherBeneficiary = \App\Models\Beneficiary::factory()->create([
            'policyholder_id' => $otherHolder->id,
            'member_number' => $s->beneficiary->member_number,
        ]);
        $otherPolicy = \App\Models\Policy::factory()->create(['subscriber_id' => $otherSubscriber->id]);

        $this->actingAs($s->agent)
            ->post(route('facility.requests.store'), [
                'insurer_id' => $s->otherInsurer->id,
                'coverage_selection' => $s->beneficiary->id.':'.$otherPolicy->id,
                'acts' => [['medical_act_id' => $s->echo->id, 'billed_amount' => 10000]],
            ])
            ->assertSessionHasErrors('request');

        $this->assertSame(0, AuthorizationRequest::query()->count());
        $this->assertNotSame($s->beneficiary->id, $otherBeneficiary->id);
    }

    public function test_only_facility_agents_can_create_requests(): void
    {
        $s = Scenario::create();
        $payload = $this->payload($s, [['medical_act_id' => $s->echo->id, 'billed_amount' => 10000]]);

        $this->actingAs($s->manager)->post(route('facility.requests.store'), $payload)->assertForbidden();
        $this->actingAs($s->admin)->post(route('facility.requests.store'), $payload)->assertForbidden();
        $this->assertSame(0, AuthorizationRequest::query()->count());
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('facility.requests.index'))->assertRedirect(route('login'));
        $this->post(route('facility.requests.store'), [])->assertRedirect(route('login'));
    }

    public function test_create_form_and_empty_list_are_displayed(): void
    {
        $s = Scenario::create();

        $this->actingAs($s->agent)->get(route('facility.requests.create'))
            ->assertOk()
            ->assertSee('Assureur')
            ->assertSee($s->insurer->name);
        $this->actingAs($s->agent)->get(route('facility.requests.index'))->assertOk()->assertSee('Aucune demande pour le moment');
    }

    public function test_related_request_options_identify_the_beneficiary_for_frontend_filtering(): void
    {
        $s = Scenario::create();
        $this->actingAs($s->agent)->post(route('facility.requests.store'), $this->payload($s, [
            ['medical_act_id' => $s->echo->id, 'billed_amount' => 10000],
        ]));
        $request = AuthorizationRequest::query()->firstOrFail();
        $request->update(['status' => RequestStatus::Approved]);

        $this->get(route('facility.requests.create', [
                'insurer_id' => $s->insurer->id,
                'search' => $s->beneficiary->member_number,
            ]))
            ->assertOk()
            ->assertSee('id="related_request_id"', false)
            ->assertSee('data-beneficiary-id="'.$s->beneficiary->id.'"', false)
            ->assertSee('#'.$request->id.' · '.$s->beneficiary->name);
    }
}
