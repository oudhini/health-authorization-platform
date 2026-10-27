<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\Scenario;
use Tests\TestCase;

final class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_audit_an_organization_user(): void
    {
        $scenario = Scenario::create();
        $payload = [
            'name' => 'Nouvel agent',
            'email' => 'new.agent@demo.test',
            'role' => 'facility_agent',
            'organization_id' => $scenario->facility->id,
            'password' => 'a-strong-demo-password',
            'password_confirmation' => 'a-strong-demo-password',
            'is_active' => '1',
        ];

        $this->actingAs($scenario->admin)
            ->followingRedirects()
            ->post(route('admin.users.store'), $payload)
            ->assertOk()
            ->assertSee('Le compte a été créé.')
            ->assertSee('new.agent@demo.test');

        $user = User::query()->where('email', $payload['email'])->firstOrFail();
        $this->assertSame($scenario->facility->id, $user->organization_id);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check($payload['password'], $user->password));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.created',
            'user_id' => $scenario->admin->id,
            'subject_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('audit_logs', ['metadata' => $payload['password']]);
    }

    public function test_user_role_must_match_organization_type(): void
    {
        $scenario = Scenario::create();

        $this->actingAs($scenario->admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Compte incohérent',
                'email' => 'invalid.role@demo.test',
                'role' => 'facility_agent',
                'organization_id' => $scenario->insurer->id,
                'password' => 'a-strong-demo-password',
                'password_confirmation' => 'a-strong-demo-password',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('organization_id');

        $this->assertDatabaseMissing('users', ['email' => 'invalid.role@demo.test']);
    }

    public function test_only_platform_admins_can_manage_accounts(): void
    {
        $scenario = Scenario::create();

        foreach ([$scenario->agent, $scenario->manager] as $user) {
            $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        }
    }

    public function test_admin_can_disable_an_account_and_disabled_user_cannot_log_in(): void
    {
        $scenario = Scenario::create();

        $this->actingAs($scenario->admin)
            ->put(route('admin.users.update', $scenario->agent), [
                'name' => $scenario->agent->name,
                'email' => $scenario->agent->email,
                'role' => 'facility_agent',
                'organization_id' => $scenario->facility->id,
                'password' => '',
                'password_confirmation' => '',
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.users.index'));

        auth()->logout();
        $this->post(route('login.store'), ['email' => $scenario->agent->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.updated',
            'subject_id' => $scenario->agent->id,
        ]);
    }

    public function test_admin_role_changes_are_recorded_without_password_data(): void
    {
        $scenario = Scenario::create();

        $this->actingAs($scenario->admin)
            ->put(route('admin.users.update', $scenario->agent), [
                'name' => $scenario->agent->name,
                'email' => $scenario->agent->email,
                'role' => 'insurer_manager',
                'organization_id' => $scenario->insurer->id,
                'password' => '',
                'password_confirmation' => '',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame('insurer_manager', $scenario->agent->fresh()->role->value);
        $audit = AuditLog::query()->where('action', 'user.updated')->firstOrFail();
        $this->assertSame('facility_agent', $audit->metadata['old_role']);
        $this->assertSame('insurer_manager', $audit->metadata['new_role']);
        $this->assertArrayNotHasKey('password', $audit->metadata);
    }

    public function test_admin_cannot_disable_themselves_and_can_disable_another_admin_while_one_remains(): void
    {
        $scenario = Scenario::create();

        $this->actingAs($scenario->admin)
            ->from(route('admin.users.edit', $scenario->admin))
            ->put(route('admin.users.update', $scenario->admin), [
                'name' => $scenario->admin->name,
                'email' => $scenario->admin->email,
                'role' => 'platform_admin',
                'organization_id' => '',
                'password' => '',
                'password_confirmation' => '',
                'is_active' => '0',
            ])
            ->assertSessionHasErrors('role');

        $this->assertTrue($scenario->admin->fresh()->is_active);

        $anotherAdmin = User::factory()->platformAdmin()->create();
        $this->actingAs($scenario->admin)
            ->put(route('admin.users.update', $anotherAdmin), [
                'name' => $anotherAdmin->name,
                'email' => $anotherAdmin->email,
                'role' => 'platform_admin',
                'organization_id' => '',
                'password' => '',
                'password_confirmation' => '',
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($anotherAdmin->fresh()->is_active);
        $this->assertTrue($scenario->admin->fresh()->is_active);
    }

    public function test_deactivated_account_session_is_ended_on_its_next_request(): void
    {
        $scenario = Scenario::create();
        $scenario->agent->is_active = false;
        $scenario->agent->save();

        $this->actingAs($scenario->agent)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.inactive_session_ended',
            'user_id' => $scenario->agent->id,
        ]);
    }
}
