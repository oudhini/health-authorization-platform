<?php

declare(strict_types=1);

namespace Tests\Feature\Insurer;

use App\Actions\Requests\CreateAuthorizationRequest;
use App\Domain\Requests\RequestStatus;
use App\Models\Authorization;
use App\Models\AuthorizationRequest;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Scenario;
use Tests\TestCase;

final class DecisionTest extends TestCase
{
    use RefreshDatabase;

    private function submitted(Scenario $scenario, int $billed = 12000): AuthorizationRequest
    {
        $request = app(CreateAuthorizationRequest::class)->handle(
            $scenario->agent,
            $scenario->insurer->id,
            $scenario->beneficiary->id,
            $scenario->policy->id,
            [['medical_act_id' => $scenario->echo->id, 'billed_amount' => $billed]],
        );
        $request->update(['status' => RequestStatus::Submitted, 'submitted_at' => now()]);

        return $request;
    }

    public function test_approval_generates_a_bon_with_frozen_totals_and_one_week_validity(): void
    {
        $scenario = Scenario::create(coverageRate: 80);
        $request = $this->submitted($scenario);

        $this->actingAs($scenario->manager)
            ->post(route('insurer.requests.approve', $request))
            ->assertRedirect(route('insurer.requests.show', $request))
            ->assertSessionHasNoErrors();

        $request->refresh();
        $authorization = Authorization::query()->firstOrFail();
        $today = CarbonImmutable::today();

        $this->assertSame(RequestStatus::Approved, $request->status);
        $this->assertSame($scenario->manager->id, $request->decided_by);
        $this->assertNotNull($request->decided_at);
        $this->assertSame($request->id, $authorization->authorization_request_id);
        $this->assertSame($today->toDateString(), $authorization->valid_from->toDateString());
        $this->assertSame($today->addDays(7)->toDateString(), $authorization->valid_until->toDateString());
        $this->assertSame([12000, 8000, 4000, 2000], [
            $authorization->billed_total,
            $authorization->insurer_total,
            $authorization->patient_total,
            $authorization->overrun_total,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'request.approved', 'user_id' => $scenario->manager->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'authorization.generated', 'user_id' => $scenario->manager->id]);
    }

    public function test_validity_length_comes_from_configuration(): void
    {
        config(['authorization.validity_days' => 10]);
        $scenario = Scenario::create();
        $request = $this->submitted($scenario);

        $this->actingAs($scenario->manager)->post(route('insurer.requests.approve', $request));

        $authorization = Authorization::query()->firstOrFail();
        $this->assertSame(
            CarbonImmutable::today()->addDays(10)->toDateString(),
            $authorization->valid_until->toDateString(),
        );
    }

    public function test_rejection_requires_a_reason_and_persists_it(): void
    {
        $scenario = Scenario::create();
        $request = $this->submitted($scenario);

        $this->actingAs($scenario->manager)
            ->post(route('insurer.requests.reject', $request), [])
            ->assertSessionHasErrors('note');
        $this->assertSame(RequestStatus::Submitted, $request->fresh()->status);

        $this->post(route('insurer.requests.reject', $request), ['note' => 'Acte non couvert par le contrat.'])
            ->assertSessionHasNoErrors();

        $request->refresh();
        $this->assertSame(RequestStatus::Rejected, $request->status);
        $this->assertSame('Acte non couvert par le contrat.', $request->decision_note);
        $this->assertSame(0, Authorization::query()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'request.rejected']);
    }

    public function test_requested_information_can_be_resubmitted_and_approved(): void
    {
        $scenario = Scenario::create();
        $request = $this->submitted($scenario);
        $message = 'Merci de joindre l\'ordonnance.';

        $this->actingAs($scenario->manager)
            ->post(route('insurer.requests.request-information', $request), ['note' => $message])
            ->assertSessionHasNoErrors();

        $request->refresh();
        $this->assertSame(RequestStatus::InfoRequested, $request->status);
        $this->assertSame($message, $request->decision_note);

        $this->actingAs($scenario->agent)
            ->get(route('facility.requests.show', $request))
            ->assertOk()
            ->assertSee('Merci de joindre');

        $this->post(route('facility.requests.submit', $request), [
            'response' => 'Ordonnance jointe et transmise à l’assureur.',
        ])->assertSessionHasNoErrors();
        $this->assertSame(RequestStatus::Submitted, $request->fresh()->status);
        $this->assertSame(2, $request->messages()->count());

        $this->actingAs($scenario->manager)
            ->get(route('insurer.requests.show', $request))
            ->assertOk()
            ->assertSee('Échanges avec')
            ->assertSee('Ordonnance jointe');

        $this->actingAs($scenario->manager)->post(route('insurer.requests.approve', $request));

        $request->refresh();
        $this->assertSame(RequestStatus::Approved, $request->status);
        $this->assertNull($request->decision_note);
    }

    public function test_a_request_cannot_be_approved_twice(): void
    {
        $scenario = Scenario::create();
        $request = $this->submitted($scenario);

        $this->actingAs($scenario->manager)->post(route('insurer.requests.approve', $request));
        $this->post(route('insurer.requests.approve', $request))->assertSessionHasErrors('request');

        $this->assertSame(1, Authorization::query()->count());
        $this->assertSame(1, \App\Models\AuditLog::query()->where('action', 'request.approved')->count());
    }

    public function test_final_requests_cannot_be_decided_again(): void
    {
        $scenario = Scenario::create();
        $request = $this->submitted($scenario);
        $this->actingAs($scenario->manager)
            ->post(route('insurer.requests.reject', $request), ['note' => 'Refus pour test.']);

        $this->post(route('insurer.requests.approve', $request))->assertSessionHasErrors('request');
        $this->assertSame(RequestStatus::Rejected, $request->fresh()->status);
        $this->assertSame(0, Authorization::query()->count());
    }

    public function test_a_draft_is_invisible_to_the_insurer(): void
    {
        $scenario = Scenario::create();
        $draft = app(CreateAuthorizationRequest::class)->handle(
            $scenario->agent,
            $scenario->insurer->id,
            $scenario->beneficiary->id,
            $scenario->policy->id,
            [['medical_act_id' => $scenario->echo->id, 'billed_amount' => 10000]],
        );

        $this->actingAs($scenario->manager);
        $this->get(route('insurer.requests.show', $draft))->assertNotFound();
        $this->post(route('insurer.requests.approve', $draft))->assertNotFound();
        $this->assertSame(RequestStatus::Draft, $draft->fresh()->status);
    }

    public function test_another_insurer_cannot_view_or_decide_the_request(): void
    {
        $scenario = Scenario::create();
        $request = $this->submitted($scenario);

        $this->actingAs($scenario->otherManager);
        $this->get(route('insurer.requests.show', $request))->assertNotFound();
        $this->post(route('insurer.requests.approve', $request))->assertNotFound();
        $this->post(route('insurer.requests.reject', $request), ['note' => 'Refus illégitime.'])->assertNotFound();

        $this->assertSame(RequestStatus::Submitted, $request->fresh()->status);
    }

    public function test_facility_agents_and_admins_cannot_decide_or_list_insurer_requests(): void
    {
        $scenario = Scenario::create();
        $request = $this->submitted($scenario);

        foreach ([$scenario->agent, $scenario->admin] as $user) {
            $this->actingAs($user);
            $this->post(route('insurer.requests.approve', $request))->assertForbidden();
            $this->post(route('insurer.requests.reject', $request), ['note' => 'Refus illégitime.'])->assertForbidden();
            $this->get(route('insurer.requests.index'))->assertForbidden();
        }

        $this->assertSame(RequestStatus::Submitted, $request->fresh()->status);
    }

    public function test_insurer_list_is_scoped_and_can_be_filtered_without_showing_drafts(): void
    {
        $scenario = Scenario::create();
        $this->submitted($scenario);
        $approved = $this->submitted($scenario);
        $approved->update(['status' => RequestStatus::Approved]);
        app(CreateAuthorizationRequest::class)->handle(
            $scenario->agent,
            $scenario->insurer->id,
            $scenario->beneficiary->id,
            $scenario->policy->id,
            [['medical_act_id' => $scenario->echo->id, 'billed_amount' => 10000]],
        );

        $all = $this->actingAs($scenario->manager)->get(route('insurer.requests.index'))->assertOk();
        $this->assertCount(2, $all->viewData('requests'));

        $filtered = $this->get(route('insurer.requests.index', ['status' => 'approved']))->assertOk();
        $this->assertSame([$approved->id], $filtered->viewData('requests')->pluck('id')->all());

        $drafts = $this->get(route('insurer.requests.index', ['status' => 'draft']))->assertOk();
        $this->assertCount(2, $drafts->viewData('requests'));

        $other = $this->actingAs($scenario->otherManager)->get(route('insurer.requests.index'))->assertOk();
        $this->assertCount(0, $other->viewData('requests'));
    }

    public function test_decision_buttons_are_only_shown_while_a_request_is_submitted(): void
    {
        $scenario = Scenario::create();
        $request = $this->submitted($scenario);

        $this->actingAs($scenario->manager)
            ->get(route('insurer.requests.show', $request))
            ->assertOk()
            ->assertSee('Approuver et générer le bon');

        $this->post(route('insurer.requests.approve', $request));

        $this->get(route('insurer.requests.show', $request))
            ->assertOk()
            ->assertDontSee('Approuver et générer le bon');
    }
}
