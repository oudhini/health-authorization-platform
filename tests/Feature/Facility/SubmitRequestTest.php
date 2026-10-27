<?php

declare(strict_types=1);

namespace Tests\Feature\Facility;

use App\Actions\Requests\CreateAuthorizationRequest;
use App\Domain\Requests\RequestStatus;
use App\Enums\DocumentType;
use App\Models\AuthorizationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Scenario;
use Tests\TestCase;

/** Transitions out of draft and info_requested (docs/requirements.md, section 5.2). */
final class SubmitRequestTest extends TestCase
{
    use RefreshDatabase;

    private function draft(Scenario $s, ?int $relatedId = null): AuthorizationRequest
    {
        return app(CreateAuthorizationRequest::class)->handle(
            $s->agent,
            $s->insurer->id,
            $s->beneficiary->id,
            $s->policy->id,
            [['medical_act_id' => $s->echo->id, 'billed_amount' => 12000]],
            $relatedId,
        );
    }

    public function test_agent_submits_a_draft(): void
    {
        $s = Scenario::create();
        $request = $this->draft($s);

        $this->actingAs($s->agent)
            ->post(route('facility.requests.submit', $request))
            ->assertRedirect(route('facility.requests.show', $request))
            ->assertSessionHasNoErrors();

        $request->refresh();
        $this->assertSame(RequestStatus::Submitted, $request->status);
        $this->assertNotNull($request->submitted_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'request.submitted', 'user_id' => $s->agent->id]);
    }

    public function test_a_request_without_acts_cannot_be_submitted(): void
    {
        $s = Scenario::create();
        $request = AuthorizationRequest::create([
            'facility_id' => $s->facility->id, 'insurer_id' => $s->insurer->id,
            'beneficiary_id' => $s->beneficiary->id, 'policy_id' => $s->policy->id,
            'status' => RequestStatus::Draft, 'coverage_rate' => 100, 'created_by' => $s->agent->id,
        ]);

        $this->actingAs($s->agent)
            ->post(route('facility.requests.submit', $request))
            ->assertSessionHasErrors('request');

        $this->assertSame(RequestStatus::Draft, $request->fresh()->status);
    }

    public function test_a_request_cannot_be_submitted_once_the_policy_has_expired(): void
    {
        $s = Scenario::create();
        $request = $this->draft($s);
        $s->policy->update(['starts_on' => '2020-01-01', 'ends_on' => '2020-12-31']);

        $this->actingAs($s->agent)
            ->post(route('facility.requests.submit', $request))
            ->assertSessionHasErrors('request');

        $this->assertSame(RequestStatus::Draft, $request->fresh()->status);
    }

    public function test_an_exam_request_needs_the_medical_form_before_it_can_be_submitted(): void
    {
        Storage::fake('local');
        $s = Scenario::create();

        $consultation = $this->draft($s);
        $consultation->update(['status' => RequestStatus::Approved]);
        $exam = $this->draft($s, $consultation->id);

        $this->actingAs($s->agent)
            ->post(route('facility.requests.submit', $exam))
            ->assertSessionHasErrors('request');
        $this->assertSame(RequestStatus::Draft, $exam->fresh()->status);

        $this->post(route('facility.documents.store', $exam), [
            'type' => DocumentType::MedicalForm->value,
            'document' => UploadedFile::fake()->create('fiche.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $this->post(route('facility.requests.submit', $exam))->assertSessionHasNoErrors();
        $this->assertSame(RequestStatus::Submitted, $exam->fresh()->status);
    }

    public function test_a_submitted_request_cannot_be_submitted_again(): void
    {
        $s = Scenario::create();
        $request = $this->draft($s);

        $this->actingAs($s->agent)->post(route('facility.requests.submit', $request));
        $this->post(route('facility.requests.submit', $request))->assertSessionHasErrors('request');

        $this->assertSame(RequestStatus::Submitted, $request->fresh()->status);
        $this->assertSame(1, \App\Models\AuditLog::query()->where('action', 'request.submitted')->count());
    }

    public function test_a_request_with_requested_information_can_be_resubmitted(): void
    {
        $s = Scenario::create();
        $request = $this->draft($s);
        $request->update(['status' => RequestStatus::Submitted]);

        $this->actingAs($s->manager)
            ->post(route('insurer.requests.request-information', $request), ['note' => 'Merci de préciser le motif.'])
            ->assertSessionHasNoErrors();

        $this->actingAs($s->agent)
            ->post(route('facility.requests.submit', $request))
            ->assertSessionHasErrors('request');

        $this->post(route('facility.requests.submit', $request), [
            'response' => 'Le médecin a confirmé le motif clinique.',
        ])
            ->assertSessionHasNoErrors();

        $this->assertSame(RequestStatus::Submitted, $request->fresh()->status);
        $this->assertSame(2, $request->messages()->count());
        $this->assertDatabaseHas('authorization_request_messages', [
            'authorization_request_id' => $request->id,
            'type' => 'facility_response',
            'body' => 'Le médecin a confirmé le motif clinique.',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'request.response_submitted',
            'user_id' => $s->agent->id,
        ]);
    }

    public function test_a_new_document_is_a_valid_response_without_a_text_message(): void
    {
        Storage::fake('local');
        $s = Scenario::create();
        $request = $this->draft($s);
        $request->update(['status' => RequestStatus::Submitted]);

        $this->actingAs($s->manager)
            ->post(route('insurer.requests.request-information', $request), ['note' => 'Veuillez joindre un justificatif.'])
            ->assertSessionHasNoErrors();

        $this->actingAs($s->agent)
            ->post(route('facility.documents.store', $request), [
                'type' => DocumentType::Other->value,
                'document' => UploadedFile::fake()->create('complement.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $this->post(route('facility.requests.submit', $request))->assertSessionHasNoErrors();

        $this->assertSame(RequestStatus::Submitted, $request->fresh()->status);
        $this->assertSame(1, $request->messages()->where('type', 'facility_response')->count());
        $this->assertSame(1, $request->documents()->count());
    }

    public function test_a_draft_can_be_cancelled_but_not_a_submitted_request(): void
    {
        $s = Scenario::create();
        $draft = $this->draft($s);
        $sent = $this->draft($s);
        $sent->update(['status' => RequestStatus::Submitted]);

        $this->actingAs($s->agent)->post(route('facility.requests.cancel', $draft))->assertSessionHasNoErrors();
        $this->post(route('facility.requests.cancel', $sent))->assertSessionHasErrors('request');

        $this->assertSame(RequestStatus::Cancelled, $draft->fresh()->status);
        $this->assertSame(RequestStatus::Submitted, $sent->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'request.cancelled']);
    }

    public function test_a_cancelled_request_is_final(): void
    {
        $s = Scenario::create();
        $request = $this->draft($s);

        $this->actingAs($s->agent)->post(route('facility.requests.cancel', $request));
        $this->post(route('facility.requests.submit', $request))->assertSessionHasErrors('request');

        $this->assertSame(RequestStatus::Cancelled, $request->fresh()->status);
    }

    public function test_show_page_displays_the_priced_acts_and_the_actions_of_a_draft(): void
    {
        $s = Scenario::create(coverageRate: 80);
        $request = $this->draft($s);

        $this->actingAs($s->agent)
            ->get(route('facility.requests.show', $request))
            ->assertOk()
            ->assertSee('Brouillon')
            ->assertSee('12 000 FCFA')
            ->assertSee('8 000 FCFA')
            ->assertSee('4 000 FCFA')
            ->assertSee('Envoyer à l');
    }
}
