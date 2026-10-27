<?php

declare(strict_types=1);

namespace Tests\Feature\Facility;

use App\Models\Beneficiary;
use App\Models\Policy;
use App\Models\Policyholder;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Scenario;
use Tests\TestCase;

final class BeneficiarySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_accepts_a_name_or_member_number_and_scopes_results_to_the_selected_insurer(): void
    {
        $scenario = Scenario::create();
        $scenario->facility->partnerInsurers()->attach($scenario->otherInsurer);
        $subscriber = Subscriber::factory()->create(['insurer_id' => $scenario->otherInsurer->id]);
        $holder = Policyholder::factory()->create(['subscriber_id' => $subscriber->id]);
        $otherBeneficiary = Beneficiary::factory()->create([
            'name' => 'Bénéficiaire Beta Exemple',
            'member_number' => $scenario->beneficiary->member_number,
            'policyholder_id' => $holder->id,
        ]);
        $otherPolicy = Policy::factory()->rate(65)->create(['subscriber_id' => $subscriber->id]);

        $this->actingAs($scenario->agent)
            ->get(route('facility.requests.create', [
                'insurer_id' => $scenario->insurer->id,
                'search' => $scenario->beneficiary->name,
            ]))
            ->assertOk()
            ->assertSee($scenario->beneficiary->name)
            ->assertSee($scenario->policy->number)
            ->assertDontSee($otherBeneficiary->name);

        $this->get(route('facility.requests.create', [
            'insurer_id' => $scenario->otherInsurer->id,
            'search' => $scenario->beneficiary->member_number,
        ]))
            ->assertOk()
            ->assertSee($otherBeneficiary->name)
            ->assertSee($otherPolicy->number)
            ->assertDontSee($scenario->beneficiary->name);
    }

    public function test_a_non_partner_insurer_cannot_be_searched(): void
    {
        $scenario = Scenario::create();

        $this->actingAs($scenario->agent)
            ->get(route('facility.requests.create', [
                'insurer_id' => $scenario->otherInsurer->id,
                'search' => 'DEMO',
            ]))
            ->assertNotFound();
    }

    public function test_beneficiaries_without_an_active_policy_do_not_appear_in_search(): void
    {
        $scenario = Scenario::create();
        $scenario->policy->update(['starts_on' => '2020-01-01', 'ends_on' => '2020-12-31']);

        $this->actingAs($scenario->agent)
            ->get(route('facility.requests.create', [
                'insurer_id' => $scenario->insurer->id,
                'search' => $scenario->beneficiary->member_number,
            ]))
            ->assertOk()
            ->assertSee('Aucun bénéficiaire avec une couverture active');
    }

    public function test_search_rejects_wildcards_and_very_long_terms(): void
    {
        $scenario = Scenario::create();

        $this->actingAs($scenario->agent)
            ->get(route('facility.requests.create', [
                'insurer_id' => $scenario->insurer->id,
                'search' => '%_',
            ]))
            ->assertSessionHasErrors('search');
    }
}
