<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Scenario;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_and_login_pages_are_public(): void
    {
        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();
    }

    public function test_each_role_logs_in_and_lands_on_the_dashboard(): void
    {
        $s = Scenario::create();

        foreach ([$s->agent, $s->manager, $s->admin] as $user) {
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($user);

            $this->get(route('dashboard'))
                ->assertOk()
                ->assertSee($user->role->label());

            $this->post('/logout');
            $this->assertGuest();
        }
    }

    public function test_successful_login_is_written_to_the_audit_log(): void
    {
        $s = Scenario::create();

        $this->post('/login', ['email' => $s->agent->email, 'password' => 'password']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login_succeeded',
            'user_id' => $s->agent->id,
        ]);
    }

    public function test_wrong_password_is_rejected_with_a_generic_message_and_audited(): void
    {
        $s = Scenario::create();

        $this->from('/login')
            ->post('/login', ['email' => $s->agent->email, 'password' => 'wrong-password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Identifiants incorrects.']);

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login_failed', 'user_id' => null]);
    }

    public function test_unknown_email_gets_the_same_message_as_a_wrong_password(): void
    {
        $this->from('/login')
            ->post('/login', ['email' => 'nobody@demo.test', 'password' => 'whatever'])
            ->assertSessionHasErrors(['email' => 'Identifiants incorrects.']);
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_repeated_failures_are_throttled(): void
    {
        $s = Scenario::create();
        $payload = ['email' => $s->agent->email, 'password' => 'wrong-password'];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', $payload)->assertStatus(302);
        }

        $this->post('/login', $payload)->assertStatus(429);
    }

    public function test_logout_ends_the_session_and_is_audited(): void
    {
        $user = User::factory()->platformAdmin()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.logout', 'user_id' => $user->id]);
    }

    public function test_guests_cannot_reach_the_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_logged_in_users_are_sent_to_the_dashboard_from_the_home_page(): void
    {
        $user = User::factory()->platformAdmin()->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
    }
}
