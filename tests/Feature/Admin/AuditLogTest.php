<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Scenario;
use Tests\TestCase;

final class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_read_the_audit_log(): void
    {
        $s = Scenario::create();
        AuditLog::create(['user_id' => $s->agent->id, 'action' => AuditAction::RequestCreated]);

        $this->actingAs($s->admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('request.created');
    }

    public function test_empty_log_shows_an_empty_state(): void
    {
        $s = Scenario::create();

        $this->actingAs($s->admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('Aucune action enregistrée');
    }

    public function test_audit_log_is_paginated(): void
    {
        $s = Scenario::create();
        foreach (range(1, 30) as $unused) {
            AuditLog::create(['user_id' => $s->agent->id, 'action' => AuditAction::RequestCreated]);
        }

        $response = $this->actingAs($s->admin)->get(route('admin.audit-logs.index'))->assertOk();

        $this->assertCount(25, $response->viewData('logs'));
    }

    public function test_other_roles_cannot_read_the_audit_log(): void
    {
        $s = Scenario::create();

        $this->actingAs($s->agent)->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->actingAs($s->manager)->get(route('admin.audit-logs.index'))->assertForbidden();
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('admin.audit-logs.index'))->assertRedirect(route('login'));
    }
}
