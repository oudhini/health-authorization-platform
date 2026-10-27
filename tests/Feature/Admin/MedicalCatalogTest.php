<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\MedicalAct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Scenario;
use Tests\TestCase;

final class MedicalCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_act_with_its_initial_tariff(): void
    {
        $scenario = Scenario::create();

        $this->actingAs($scenario->admin)
            ->post(route('admin.medical-catalog.store'), [
                'code' => 'LAB-DEMO',
                'label' => 'Analyse fictive',
                'amount' => 7500,
                'effective_from' => now()->toDateString(),
            ])
            ->assertRedirect(route('admin.medical-catalog.index'));

        $this->get(route('admin.medical-catalog.index'))->assertOk()->assertSee('LAB-DEMO');
        $act = MedicalAct::query()->where('code', 'LAB-DEMO')->firstOrFail();
        $this->assertTrue($act->is_active);
        $this->assertSame(
            7500,
            $act->tariffs()->whereDate('effective_from', now()->toDateString())->value('amount'),
        );
        $this->assertDatabaseHas('audit_logs', ['action' => 'medical_act.created', 'subject_id' => $act->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'official_tariff.created', 'metadata->amount' => 7500]);
    }

    public function test_tariff_changes_are_appended_with_a_future_effective_date(): void
    {
        $scenario = Scenario::create();
        $tomorrow = now()->addDay()->toDateString();

        $this->actingAs($scenario->admin)
            ->post(route('admin.medical-catalog.tariffs.store', $scenario->consultation), [
                'amount' => 6000,
                'effective_from' => $tomorrow,
            ])
            ->assertRedirect(route('admin.medical-catalog.index'));

        $this->assertSame(Scenario::CONSULTATION_TARIFF, $scenario->consultation->tariffs()->whereDate('effective_from', '<', $tomorrow)->value('amount'));
        $this->assertSame(6000, $scenario->consultation->tariffs()->whereDate('effective_from', $tomorrow)->value('amount'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'official_tariff.created']);
    }

    public function test_duplicate_tariff_effective_date_is_rejected(): void
    {
        $scenario = Scenario::create();
        $effectiveFrom = now()->toDateString();
        $scenario->consultation->tariffs()->create([
            'amount' => 6000,
            'effective_from' => $effectiveFrom,
        ]);

        $this->actingAs($scenario->admin)
            ->post(route('admin.medical-catalog.tariffs.store', $scenario->consultation), [
                'amount' => 6500,
                'effective_from' => $effectiveFrom,
            ])
            ->assertSessionHasErrors('effective_from');
    }

    public function test_inactive_acts_are_hidden_from_new_requests_and_cannot_be_submitted_by_tampering(): void
    {
        $scenario = Scenario::create();
        $scenario->consultation->update(['is_active' => false]);

        $this->actingAs($scenario->agent)
            ->get(route('facility.requests.create'))
            ->assertOk()
            ->assertDontSee('CONSULT');

        $this->post(route('facility.requests.store'), [
            'insurer_id' => $scenario->insurer->id,
            'coverage_selection' => $scenario->beneficiary->id.':'.$scenario->policy->id,
            'acts' => [['medical_act_id' => $scenario->consultation->id, 'billed_amount' => 5000]],
        ])->assertSessionHasErrors('request');

        $this->assertDatabaseCount('authorization_requests', 0);
    }

    public function test_only_platform_admins_can_manage_the_medical_catalog(): void
    {
        $scenario = Scenario::create();

        foreach ([$scenario->agent, $scenario->manager] as $user) {
            $this->actingAs($user)->get(route('admin.medical-catalog.index'))->assertForbidden();
        }
    }
}
