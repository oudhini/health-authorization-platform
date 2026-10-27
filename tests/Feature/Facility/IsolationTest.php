<?php

declare(strict_types=1);

namespace Tests\Feature\Facility;

use App\Actions\Requests\CreateAuthorizationRequest;
use App\Models\AuthorizationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Scenario;
use Tests\TestCase;

/** One organization never reaches another one's data (docs/requirements.md, 8.1). */
final class IsolationTest extends TestCase
{
    use RefreshDatabase;

    private function draft(Scenario $s): AuthorizationRequest
    {
        return app(CreateAuthorizationRequest::class)->handle(
            $s->agent,
            $s->insurer->id,
            $s->beneficiary->id,
            $s->policy->id,
            [['medical_act_id' => $s->echo->id, 'billed_amount' => 10000]],
        );
    }

    public function test_another_facility_gets_a_404_on_every_action(): void
    {
        Storage::fake('local');
        $s = Scenario::create();
        $request = $this->draft($s);

        $this->actingAs($s->otherAgent);

        $this->get(route('facility.requests.show', $request))->assertNotFound();
        $this->post(route('facility.requests.submit', $request))->assertNotFound();
        $this->post(route('facility.requests.cancel', $request))->assertNotFound();
        $this->post(route('facility.documents.store', $request), [
            'type' => 'other',
            'document' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
        ])->assertNotFound();
    }

    public function test_the_list_only_contains_the_facilitys_own_requests(): void
    {
        $s = Scenario::create();
        $mine = $this->draft($s);

        $this->actingAs($s->otherAgent)
            ->get(route('facility.requests.index'))
            ->assertOk()
            ->assertSee('Aucune demande pour le moment');

        $this->actingAs($s->agent)->get(route('facility.requests.index'))->assertOk()->assertSee($s->beneficiary->name);
    }

    public function test_insurer_and_admin_cannot_use_the_facility_portal(): void
    {
        $s = Scenario::create();
        $request = $this->draft($s);

        foreach ([$s->manager, $s->admin] as $user) {
            $this->actingAs($user);
            $this->get(route('facility.requests.index'))->assertForbidden();
            $this->get(route('facility.requests.create'))->assertForbidden();
            $this->get(route('facility.requests.show', $request))->assertForbidden();
            $this->post(route('facility.requests.submit', $request))->assertForbidden();
        }
    }
}
