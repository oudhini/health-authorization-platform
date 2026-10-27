<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Scenario;
use Tests\TestCase;

final class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_rename_an_organization_without_changing_its_type(): void
    {
        $scenario = Scenario::create();

        $this->actingAs($scenario->admin)
            ->post(route('admin.organizations.store'), [
                'name' => 'Clinique Démo Gamma',
                'type' => 'facility',
            ])
            ->assertRedirect(route('admin.organizations.index'));

        $this->get(route('admin.organizations.index'))->assertOk()->assertSee('Clinique Démo Gamma');
        $organization = Organization::query()->where('name', 'Clinique Démo Gamma')->firstOrFail();
        $this->put(route('admin.organizations.update', $organization), [
            'name' => 'Centre Démo Gamma',
            'type' => 'insurer',
        ])->assertRedirect(route('admin.organizations.index'));

        $organization->refresh();
        $this->assertSame('Centre Démo Gamma', $organization->name);
        $this->assertSame('facility', $organization->type->value);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'organization.created',
            'subject_id' => $organization->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'organization.renamed',
            'subject_id' => $organization->id,
        ]);
    }

    public function test_non_admin_cannot_manage_organizations(): void
    {
        $scenario = Scenario::create();

        $this->actingAs($scenario->manager)
            ->get(route('admin.organizations.index'))
            ->assertForbidden();
    }
}
