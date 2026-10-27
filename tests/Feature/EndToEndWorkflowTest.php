<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Requests\RequestStatus;
use App\Models\Authorization;
use App\Models\AuthorizationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Scenario;
use Tests\TestCase;

final class EndToEndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_consultation_and_related_exam_workflows_from_creation_to_decision(): void
    {
        Storage::fake('local');
        $scenario = Scenario::create(coverageRate: 80);

        $this->actingAs($scenario->agent)->post(route('facility.requests.store'), [
            'insurer_id' => $scenario->insurer->id,
            'coverage_selection' => $scenario->beneficiary->id.':'.$scenario->policy->id,
            'acts' => [['medical_act_id' => $scenario->echo->id, 'billed_amount' => 12000]],
        ])->assertSessionHasNoErrors();
        $consultation = AuthorizationRequest::query()->firstOrFail();
        $this->post(route('facility.requests.submit', $consultation))->assertSessionHasNoErrors();

        $this->actingAs($scenario->manager)
            ->post(route('insurer.requests.request-information', $consultation), [
                'note' => 'Veuillez joindre un complément et préciser la demande.',
            ])->assertSessionHasNoErrors();
        $this->actingAs($scenario->agent)
            ->post(route('facility.documents.store', $consultation), [
                'type' => 'other',
                'document' => UploadedFile::fake()->create('complement.pdf', 80, 'application/pdf'),
            ])->assertSessionHasNoErrors();
        $this->post(route('facility.requests.submit', $consultation), [
            'response' => 'Le document demandé est joint; la consultation concerne le symptôme décrit.',
        ])->assertSessionHasNoErrors();
        $this->assertSame(2, $consultation->messages()->count());

        $this->actingAs($scenario->manager)
            ->post(route('insurer.requests.approve', $consultation))
            ->assertSessionHasNoErrors();
        $authorization = Authorization::query()->firstOrFail();
        $this->assertSame([8000, 4000, 2000], [
            $authorization->insurer_total,
            $authorization->patient_total,
            $authorization->overrun_total,
        ]);
        $this->actingAs($scenario->agent)
            ->get(route('authorizations.show', $authorization))
            ->assertOk()
            ->assertSee('4 000 FCFA');

        $this->post(route('facility.requests.store'), [
            'insurer_id' => $scenario->insurer->id,
            'coverage_selection' => $scenario->beneficiary->id.':'.$scenario->policy->id,
            'related_request_id' => $consultation->id,
            'acts' => [['medical_act_id' => $scenario->consultation->id, 'billed_amount' => 5000]],
        ])->assertSessionHasNoErrors();
        $exam = AuthorizationRequest::query()->latest('id')->firstOrFail();
        $this->assertSame($consultation->id, $exam->related_request_id);

        $this->post(route('facility.requests.submit', $exam))->assertSessionHasErrors('request');
        $this->post(route('facility.documents.store', $exam), [
            'type' => 'medical_form',
            'document' => UploadedFile::fake()->create('fiche.pdf', 80, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $this->post(route('facility.requests.submit', $exam))->assertSessionHasNoErrors();

        $this->actingAs($scenario->manager)
            ->post(route('insurer.requests.reject', $exam), ['note' => 'Examen non prévu au contrat.'])
            ->assertSessionHasNoErrors();
        $this->actingAs($scenario->agent)
            ->get(route('facility.requests.show', $exam))
            ->assertOk()
            ->assertSee('Refusée')
            ->assertSee('Examen non prévu au contrat.');

        $this->assertSame(RequestStatus::Approved, $consultation->fresh()->status);
        $this->assertSame(RequestStatus::Rejected, $exam->fresh()->status);

        foreach ([
            'request.created',
            'request.submitted',
            'request.info_requested',
            'request.response_submitted',
            'request.approved',
            'authorization.generated',
            'authorization.viewed',
            'document.uploaded',
            'request.rejected',
        ] as $action) {
            $this->assertDatabaseHas('audit_logs', ['action' => $action]);
        }
        $this->actingAs($scenario->admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('request.rejected');
    }

    public function test_related_exam_can_be_approved_and_generate_a_printable_authorization(): void
    {
        Storage::fake('local');
        $scenario = Scenario::create(coverageRate: 80);

        $this->actingAs($scenario->agent)->post(route('facility.requests.store'), [
            'insurer_id' => $scenario->insurer->id,
            'coverage_selection' => $scenario->beneficiary->id.':'.$scenario->policy->id,
            'acts' => [['medical_act_id' => $scenario->echo->id, 'billed_amount' => 12000]],
        ])->assertSessionHasNoErrors();
        $consultation = AuthorizationRequest::query()->firstOrFail();
        $consultation->update(['status' => RequestStatus::Approved]);

        $this->post(route('facility.requests.store'), [
            'insurer_id' => $scenario->insurer->id,
            'coverage_selection' => $scenario->beneficiary->id.':'.$scenario->policy->id,
            'related_request_id' => $consultation->id,
            'acts' => [['medical_act_id' => $scenario->consultation->id, 'billed_amount' => 4000]],
        ])->assertSessionHasNoErrors();
        $exam = AuthorizationRequest::query()->latest('id')->firstOrFail();

        $this->post(route('facility.documents.store', $exam), [
            'type' => 'medical_form',
            'document' => UploadedFile::fake()->create('fiche-examen.pdf', 80, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $this->post(route('facility.requests.submit', $exam))->assertSessionHasNoErrors();
        $this->actingAs($scenario->manager)
            ->post(route('insurer.requests.approve', $exam))
            ->assertSessionHasNoErrors();

        $authorization = Authorization::query()
            ->where('authorization_request_id', $exam->id)
            ->firstOrFail();
        $this->assertSame([3200, 800], [
            $authorization->insurer_total,
            $authorization->patient_total,
        ]);
        $this->actingAs($scenario->agent)
            ->get(route('authorizations.show', $authorization))
            ->assertOk()
            ->assertSee($authorization->number)
            ->assertSee('Ce bon est valable')
            ->assertSee('3 200 FCFA')
            ->assertSee('800 FCFA');
    }
}
