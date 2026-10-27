<?php

declare(strict_types=1);

namespace Tests\Feature\Facility;

use App\Domain\Requests\RequestStatus;
use App\Models\AuthorizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Scenario;
use Tests\TestCase;

final class EditDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_can_update_draft_items_and_their_calculated_amounts(): void
    {
        $scenario = Scenario::create(coverageRate: 80);
        $draft = $this->createDraft($scenario);

        $this->actingAs($scenario->agent)
            ->get(route('facility.requests.edit', $draft))
            ->assertOk()
            ->assertSee('Modifier le brouillon');

        $this->put(route('facility.requests.update', $draft), [
            'acts' => [
                ['medical_act_id' => $scenario->echo->id, 'billed_amount' => 8000],
                ['medical_act_id' => $scenario->consultation->id, 'billed_amount' => 5000],
            ],
        ])->assertRedirect(route('facility.requests.show', $draft));

        $draft->refresh()->load('items');
        $this->assertCount(2, $draft->items);
        $this->assertSame(6400, $draft->items->firstWhere('medical_act_id', $scenario->echo->id)->insurer_share);
        $this->assertSame(4000, $draft->items->firstWhere('medical_act_id', $scenario->consultation->id)->insurer_share);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'request.updated',
            'user_id' => $scenario->agent->id,
            'subject_id' => $draft->id,
        ]);
    }

    public function test_another_agent_cannot_edit_the_creator_draft(): void
    {
        $scenario = Scenario::create();
        $draft = $this->createDraft($scenario);
        $anotherAgent = User::factory()->facilityAgent($scenario->facility)->create();

        $this->actingAs($anotherAgent)
            ->get(route('facility.requests.edit', $draft))
            ->assertNotFound();
        $this->put(route('facility.requests.update', $draft), [
            'acts' => [['medical_act_id' => $scenario->echo->id, 'billed_amount' => 8000]],
        ])->assertNotFound();
    }

    public function test_submitted_requests_cannot_be_edited(): void
    {
        $scenario = Scenario::create();
        $draft = $this->createDraft($scenario);
        $draft->update(['status' => RequestStatus::Submitted]);

        $this->actingAs($scenario->agent)
            ->get(route('facility.requests.edit', $draft))
            ->assertNotFound();
    }

    public function test_existing_inactive_act_remains_selected_when_editing_a_draft(): void
    {
        $scenario = Scenario::create();
        $draft = $this->createDraft($scenario);
        $scenario->echo->update(['is_active' => false]);

        $this->actingAs($scenario->agent)
            ->get(route('facility.requests.edit', $draft))
            ->assertOk()
            ->assertSee('Échographie (désactivé)');

        $this->put(route('facility.requests.update', $draft), [
            'acts' => [['medical_act_id' => $scenario->echo->id, 'billed_amount' => 12000]],
        ])->assertRedirect(route('facility.requests.show', $draft));

        $this->assertDatabaseHas('authorization_items', [
            'authorization_request_id' => $draft->id,
            'medical_act_id' => $scenario->echo->id,
        ]);
    }

    private function createDraft(Scenario $scenario): AuthorizationRequest
    {
        $this->actingAs($scenario->agent)
            ->post(route('facility.requests.store'), [
                'insurer_id' => $scenario->insurer->id,
                'coverage_selection' => $scenario->beneficiary->id.':'.$scenario->policy->id,
                'acts' => [['medical_act_id' => $scenario->echo->id, 'billed_amount' => 12000]],
            ])
            ->assertSessionHasNoErrors();

        return AuthorizationRequest::query()->firstOrFail();
    }
}
