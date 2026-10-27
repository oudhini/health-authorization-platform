<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\Policy;
use App\Models\Policyholder;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\Scenario;
use Tests\TestCase;

final class CoverageRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_insurer_manager_only_sees_and_edits_its_own_roster(): void
    {
        $scenario = Scenario::create();
        $otherSubscriber = Subscriber::factory()->create(['insurer_id' => $scenario->otherInsurer->id]);
        $otherHolder = Policyholder::factory()->create(['subscriber_id' => $otherSubscriber->id]);
        $otherBeneficiary = Beneficiary::factory()->create(['policyholder_id' => $otherHolder->id]);
        Policy::factory()->create(['subscriber_id' => $otherSubscriber->id]);

        $this->actingAs($scenario->manager)
            ->get(route('coverage-roster.index'))
            ->assertOk()
            ->assertSee($scenario->beneficiary->member_number)
            ->assertDontSee($otherBeneficiary->member_number);
        $this->actingAs($scenario->manager)
            ->get(route('coverage-roster.edit', $otherBeneficiary))
            ->assertNotFound();
        $this->actingAs($scenario->agent)
            ->get(route('coverage-roster.index'))
            ->assertForbidden();
    }

    public function test_platform_admin_can_view_and_correct_an_insurer_record(): void
    {
        $scenario = Scenario::create();
        $this->actingAs($scenario->admin)
            ->get(route('coverage-roster.index', ['insurer_id' => $scenario->insurer->id]))
            ->assertOk()
            ->assertSee($scenario->beneficiary->member_number);

        $policy = $scenario->policy;
        $response = $this->actingAs($scenario->admin)->put(route('coverage-roster.update', $scenario->beneficiary), [
            'insurer_id' => $scenario->insurer->id,
            'policy_id' => $policy->id,
            'subscriber_name' => $scenario->subscriber->name,
            'policyholder_name' => $scenario->beneficiary->policyholder->name,
            'beneficiary_name' => 'Nom corrigé fictif',
            'birth_date' => $scenario->beneficiary->birth_date->toDateString(),
            'relationship' => 'child',
            'member_number' => $scenario->beneficiary->member_number,
            'policy_number' => $policy->number,
            'coverage_rate' => $policy->coverage_rate,
            'starts_on' => $policy->starts_on->toDateString(),
            'ends_on' => $policy->ends_on->toDateString(),
        ]);

        $response->assertRedirect(route('coverage-roster.index', ['insurer_id' => $scenario->insurer->id]));
        $this->assertSame('Nom corrigé fictif', $scenario->beneficiary->fresh()->name);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'coverage_record.updated',
            'subject_id' => $scenario->beneficiary->id,
            'user_id' => $scenario->admin->id,
        ]);
    }

    public function test_insurer_manager_can_manually_add_a_fictional_covered_beneficiary(): void
    {
        $scenario = Scenario::create();
        $response = $this->actingAs($scenario->manager)->post(route('coverage-roster.store'), [
            'subscriber_name' => 'Entreprise Fictive',
            'policyholder_name' => 'Assuré Principal Fictif',
            'beneficiary_name' => 'Enfant Fictif',
            'birth_date' => '2015-04-12',
            'relationship' => 'child',
            'member_number' => 'FAKE-ROSTER-0001',
            'policy_number' => 'POL-FAKE-ROSTER-0001',
            'coverage_rate' => 80,
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
        ]);

        $response->assertRedirect(route('coverage-roster.index', ['insurer_id' => $scenario->insurer->id]));
        $beneficiary = Beneficiary::query()->where('member_number', 'FAKE-ROSTER-0001')->firstOrFail();
        $this->assertSame($scenario->insurer->id, $beneficiary->policyholder->subscriber->insurer_id);
        $this->assertDatabaseHas('policies', [
            'number' => 'POL-FAKE-ROSTER-0001',
            'insurer_id' => $scenario->insurer->id,
            'coverage_rate' => 80,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'coverage_record.created',
            'subject_id' => $beneficiary->id,
        ]);

        $this->actingAs($scenario->agent)
            ->get(route('facility.requests.create', [
                'insurer_id' => $scenario->insurer->id,
                'search' => 'FAKE-ROSTER-0001',
            ]))
            ->assertOk()
            ->assertSee('Enfant Fictif')
            ->assertSee('FAKE-ROSTER-0001');
    }

    public function test_csv_preview_is_read_only_and_confirmation_imports_the_reviewed_file(): void
    {
        $scenario = Scenario::create(coverageRate: 80);
        $csv = $this->csv([
            $scenario->subscriber->name,
            $scenario->beneficiary->policyholder->name,
            'Nouvelle Personne Fictive',
            '2012-05-10',
            'child',
            'FAKE-CSV-1001',
            $scenario->policy->number,
            '80',
            $scenario->policy->starts_on->toDateString(),
            $scenario->policy->ends_on->toDateString(),
        ]);

        $preview = $this->actingAs($scenario->manager)->post(route('coverage-roster.import.preview'), [
            'csv_file' => $csv,
        ])->assertOk()
            ->assertSee('1 nouveau(x) bénéficiaire(s)')
            ->assertSee('FAKE-CSV-1001');
        $fingerprint = $preview->viewData('fingerprint');
        $this->assertDatabaseMissing('beneficiaries', ['member_number' => 'FAKE-CSV-1001']);

        $this->post(route('coverage-roster.import'), [
            'csv_file' => $this->csv([
                $scenario->subscriber->name,
                $scenario->beneficiary->policyholder->name,
                'Nouvelle Personne Fictive',
                '2012-05-10',
                'child',
                'FAKE-CSV-1001',
                $scenario->policy->number,
                '80',
                $scenario->policy->starts_on->toDateString(),
                $scenario->policy->ends_on->toDateString(),
            ]),
            'fingerprint' => $fingerprint,
        ])->assertRedirect(route('coverage-roster.index', ['insurer_id' => $scenario->insurer->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('beneficiaries', ['member_number' => 'FAKE-CSV-1001']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'coverage_roster.imported',
            'user_id' => $scenario->manager->id,
        ]);
    }

    public function test_csv_import_rejects_bad_rows_and_a_different_file_after_preview(): void
    {
        $scenario = Scenario::create();
        $badCsv = $this->csv([
            'Entreprise Fictive', 'Assuré Fictif', 'Nom Fictif', '2012-05-10', 'child',
            'FAKE-BAD-1', 'POL-BAD-1', '150', '2026-01-01', '2026-12-31',
        ]);
        $this->actingAs($scenario->manager)
            ->post(route('coverage-roster.import.preview'), ['csv_file' => $badCsv])
            ->assertOk()
            ->assertSee('Ligne 2');
        $this->assertSame(0, Beneficiary::query()->where('member_number', 'FAKE-BAD-1')->count());

        $good = $this->csv([
            'Entreprise Fictive', 'Assuré Fictif', 'Nom Fictif', '2012-05-10', 'child',
            'FAKE-HASH-1', 'POL-HASH-1', '80', '2026-01-01', '2026-12-31',
        ]);
        $preview = $this->post(route('coverage-roster.import.preview'), ['csv_file' => $good])->assertOk();
        $fingerprint = $preview->viewData('fingerprint');
        $changed = $this->csv([
            'Entreprise Fictive', 'Assuré Fictif', 'Nom modifié', '2012-05-10', 'child',
            'FAKE-HASH-2', 'POL-HASH-2', '80', '2026-01-01', '2026-12-31',
        ]);
        $this->post(route('coverage-roster.import'), [
            'csv_file' => $changed,
            'fingerprint' => $fingerprint,
        ])->assertSessionHasErrors('csv_file');
        $this->assertDatabaseMissing('beneficiaries', ['member_number' => 'FAKE-HASH-2']);
    }

    public function test_csv_can_reuse_member_and_policy_numbers_for_a_different_insurer(): void
    {
        $scenario = Scenario::create();
        $otherSubscriber = Subscriber::factory()->create(['insurer_id' => $scenario->otherInsurer->id]);
        $otherHolder = Policyholder::factory()->create(['subscriber_id' => $otherSubscriber->id]);
        $otherBeneficiary = Beneficiary::factory()->create([
            'policyholder_id' => $otherHolder->id,
            'member_number' => 'SHARED-MEMBER-001',
        ]);
        Policy::factory()->create([
            'subscriber_id' => $otherSubscriber->id,
            'number' => 'SHARED-POLICY-001',
        ]);
        $csv = $this->csv([
            $scenario->subscriber->name,
            $scenario->beneficiary->policyholder->name,
            'Tentative Fictive',
            '2012-05-10',
            'child',
            'SHARED-MEMBER-001',
            'SHARED-POLICY-001',
            '80',
            $scenario->policy->starts_on->toDateString(),
            $scenario->policy->ends_on->toDateString(),
        ]);

        $preview = $this->actingAs($scenario->manager)
            ->post(route('coverage-roster.import.preview'), ['csv_file' => $csv])
            ->assertOk()
            ->assertSee('1 nouveau(x) bénéficiaire(s)');

        $this->post(route('coverage-roster.import'), [
            'csv_file' => $this->csv([
                $scenario->subscriber->name,
                $scenario->beneficiary->policyholder->name,
                'Tentative Fictive',
                '2012-05-10',
                'child',
                'SHARED-MEMBER-001',
                'SHARED-POLICY-001',
                '80',
                $scenario->policy->starts_on->toDateString(),
                $scenario->policy->ends_on->toDateString(),
            ]),
            'fingerprint' => $preview->viewData('fingerprint'),
        ])->assertRedirect();

        $this->assertSame(2, Beneficiary::query()->where('member_number', 'SHARED-MEMBER-001')->count());
        $this->assertSame(2, Policy::query()->where('number', 'SHARED-POLICY-001')->count());
    }

    /** @param list<string> $row */
    private function csv(array $row): UploadedFile
    {
        $headers = [
            'subscriber_name', 'policyholder_name', 'beneficiary_name', 'birth_date', 'relationship',
            'member_number', 'policy_number', 'coverage_rate', 'starts_on', 'ends_on',
        ];
        $content = implode(',', $headers)."\r\n".implode(',', array_map(
            static fn (string $value): string => '"'.str_replace('"', '""', $value).'"',
            $row,
        ))."\r\n";

        return UploadedFile::fake()->createWithContent('roster.csv', $content);
    }
}
