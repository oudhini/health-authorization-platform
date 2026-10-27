<?php

declare(strict_types=1);

namespace Tests\Feature\Insurer;

use App\Actions\Requests\CreateAuthorizationRequest;
use App\Actions\Requests\DecideAuthorizationRequest;
use App\Domain\Requests\RequestStatus;
use App\Models\Authorization;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Scenario;
use Tests\TestCase;

final class AuthorizationBonTest extends TestCase
{
    use RefreshDatabase;

    private function approved(Scenario $scenario): Authorization
    {
        $request = app(CreateAuthorizationRequest::class)->handle(
            $scenario->agent,
            $scenario->insurer->id,
            $scenario->beneficiary->id,
            $scenario->policy->id,
            [['medical_act_id' => $scenario->echo->id, 'billed_amount' => 12000]],
        );
        $request->update(['status' => RequestStatus::Submitted]);

        return app(DecideAuthorizationRequest::class)->approve($scenario->manager, $request);
    }

    public function test_the_facility_sees_the_printable_bon_and_its_amounts(): void
    {
        $scenario = Scenario::create(coverageRate: 80);
        $authorization = $this->approved($scenario);

        $this->actingAs($scenario->agent)
            ->get(route('authorizations.show', $authorization))
            ->assertOk()
            ->assertSee($authorization->fresh()->number)
            ->assertSee('Ce bon est valable')
            ->assertSee('12 000 FCFA')
            ->assertSee('8 000 FCFA')
            ->assertSee('4 000 FCFA')
            ->assertSee('Télécharger le bon en PDF')
            ->assertSee('Imprimer cette page');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'authorization.viewed',
            'user_id' => $scenario->agent->id,
        ]);
    }

    public function test_the_request_insurer_can_see_the_bon(): void
    {
        $scenario = Scenario::create();
        $authorization = $this->approved($scenario);

        $this->actingAs($scenario->manager)->get(route('authorizations.show', $authorization))->assertOk();
    }

    public function test_authorized_users_can_download_a_server_generated_pdf_and_it_is_audited(): void
    {
        $scenario = Scenario::create(coverageRate: 80);
        $authorization = $this->approved($scenario);

        $this->actingAs($scenario->agent)
            ->get(route('authorizations.pdf', $authorization))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="'.$authorization->number.'.pdf"')
            ->assertSee('%PDF-', false)
            ->assertSee('%%EOF', false);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'authorization.downloaded',
            'user_id' => $scenario->agent->id,
            'subject_id' => $authorization->id,
        ]);

        $this->actingAs($scenario->manager)
            ->get(route('authorizations.pdf', $authorization))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertSee('%PDF-', false);
    }

    public function test_other_organizations_and_admin_cannot_see_the_bon(): void
    {
        $scenario = Scenario::create();
        $authorization = $this->approved($scenario);

        foreach ([$scenario->otherAgent, $scenario->otherManager, $scenario->admin] as $user) {
            $this->actingAs($user)->get(route('authorizations.show', $authorization))->assertNotFound();
            $this->get(route('authorizations.pdf', $authorization))->assertNotFound();
        }
        $this->assertDatabaseMissing('audit_logs', ['action' => 'authorization.downloaded']);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $scenario = Scenario::create();
        $authorization = $this->approved($scenario);

        $this->get(route('authorizations.show', $authorization))->assertRedirect(route('login'));
    }

    public function test_expired_bon_is_marked_as_expired(): void
    {
        $scenario = Scenario::create();
        $authorization = $this->approved($scenario);
        $authorization->update([
            'valid_from' => CarbonImmutable::today()->subDays(20)->toDateString(),
            'valid_until' => CarbonImmutable::today()->subDays(13)->toDateString(),
        ]);

        $this->actingAs($scenario->agent)
            ->get(route('authorizations.show', $authorization))
            ->assertOk()
            ->assertSee('Ce bon est expiré');
    }

    public function test_totals_remain_frozen_if_request_items_are_changed(): void
    {
        $scenario = Scenario::create(coverageRate: 80);
        $authorization = $this->approved($scenario);
        $authorization->authorizationRequest->items()->update(['insurer_share' => 1, 'patient_share' => 11999]);

        $this->actingAs($scenario->agent)
            ->get(route('authorizations.show', $authorization))
            ->assertOk()
            ->assertSee('8 000 FCFA')
            ->assertSee('4 000 FCFA');
        $this->assertSame(8000, $authorization->fresh()->insurer_total);
    }

    public function test_web_routes_do_not_allow_modifying_or_deleting_authorizations(): void
    {
        $scenario = Scenario::create();
        $authorization = $this->approved($scenario);

        $this->actingAs($scenario->manager);
        $this->put(route('authorizations.show', $authorization), ['insurer_total' => 1])->assertStatus(405);
        $this->patch(route('authorizations.show', $authorization), ['insurer_total' => 1])->assertStatus(405);
        $this->delete(route('authorizations.show', $authorization))->assertStatus(405);
    }
}
