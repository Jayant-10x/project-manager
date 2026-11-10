<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_component_renders_successfully(): void
    {
        Livewire::test(Login::class)
            ->assertStatus(200)
            ->assertSee('Project Coordinator CRM')
            ->assertSee('Sign in')
            ->assertDontSee('required');
    }

    public function test_validation_errors_triggered_when_fields_are_empty(): void
    {
        Livewire::test(Login::class)
            ->set('email', '')
            ->set('password', '')
            ->call('authenticate')
            ->assertHasErrors([
                'email' => 'required',
                'password' => 'required',
            ])
            ->assertSee('The email address is required.')
            ->assertSee('The password is required.');
    }

    public function test_validation_error_triggered_when_email_is_invalid(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'not-a-valid-email')
            ->set('password', 'secret123')
            ->call('authenticate')
            ->assertHasErrors(['email' => 'email'])
            ->assertSee('Please enter a valid email address.');
    }

    public function test_realtime_validation_triggers_on_email_field(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'invalid-format')
            ->assertHasErrors(['email' => 'email'])
            ->set('email', 'valid@the10xagency.in')
            ->assertHasNoErrors(['email']);
    }

    public function test_user_can_authenticate_via_livewire(): void
    {
        $user = User::factory()->create([
            'email' => 'coordinator@the10xagency.in',
            'password' => bcrypt('StrongPass123!'),
            'must_change' => false,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'coordinator@the10xagency.in')
            ->set('password', 'StrongPass123!')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertTrue((bool) $user->is_online);
        $this->assertSame(0, $user->fail_count);
    }

    public function test_livewire_login_tracks_failed_attempts_and_locks_account_after_5_failures(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@the10xagency.in',
            'password' => bcrypt('CorrectPassword123!'),
            'fail_count' => 0,
            'locked_until' => null,
        ]);

        for ($i = 1; $i <= 4; $i++) {
            $left = 5 - $i;
            Livewire::test(Login::class)
                ->set('email', 'staff@the10xagency.in')
                ->set('password', 'WrongPass')
                ->call('authenticate')
                ->assertHasErrors(['email'])
                ->assertSee("Invalid email or password. {$left} attempt(s) left.");

            $this->assertSame($i, $user->fresh()->fail_count);
            $this->assertNull($user->fresh()->locked_until);
        }

        // 5th attempt locks account
        Livewire::test(Login::class)
            ->set('email', 'staff@the10xagency.in')
            ->set('password', 'WrongPass')
            ->call('authenticate')
            ->assertHasErrors(['email'])
            ->assertSee('Account locked for 30 minutes due to 5 failed attempts.');

        $user->refresh();
        $this->assertSame(0, $user->fail_count);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());
    }

    public function test_locked_user_cannot_authenticate_via_livewire(): void
    {
        $user = User::factory()->create([
            'email' => 'locked@the10xagency.in',
            'password' => bcrypt('CorrectPassword123!'),
            'locked_until' => now()->addMinutes(20),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'locked@the10xagency.in')
            ->set('password', 'CorrectPassword123!')
            ->call('authenticate')
            ->assertHasErrors(['email'])
            ->assertSee('Account locked due to multiple failed logins.');

        $this->assertGuest();
    }

    public function test_user_with_must_change_password_is_redirected_to_password_change(): void
    {
        $user = User::factory()->create([
            'email' => 'tempuser@the10xagency.in',
            'password' => bcrypt('TempPass123!'),
            'must_change' => true,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'tempuser@the10xagency.in')
            ->set('password', 'TempPass123!')
            ->call('authenticate')
            ->assertRedirect(route('password.change'));

        $this->assertAuthenticatedAs($user);
    }
}
