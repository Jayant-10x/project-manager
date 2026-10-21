<?php

namespace Tests\Feature\Auth;

use App\Livewire\Components\NotificationDrawer;
use App\Livewire\Settings\SettingIndex;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_request_notifies_admins_and_logs_activity(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        $user = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
            'email' => 'coordinator@the10xagency.in',
        ]);

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHas('status', 'Password reset request has been sent to the admin. Please ask or wait for your temporary credentials.');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'title' => 'Password Reset Requested',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'Password Reset Requested',
        ]);
    }

    public function test_admin_can_reset_user_password_and_generate_temporary_credentials(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        $user = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
            'must_change' => false,
            'fail_count' => 5,
            'locked_until' => now()->addMinutes(30),
        ]);

        Livewire::actingAs($admin)
            ->test(SettingIndex::class)
            ->call('resetUserPassword', $user->id)
            ->assertDispatched('open-modal', 'temp-credentials-modal')
            ->assertDispatched('toast');

        $user->refresh();

        $this->assertTrue($user->must_change);
        $this->assertSame(0, $user->fail_count);
        $this->assertNull($user->locked_until);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'Reset User Password',
            'entity_id' => $user->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Password Reset by Administrator',
        ]);
    }

    public function test_non_admin_cannot_reset_user_password(): void
    {
        $coordinator = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
        ]);

        $targetUser = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Active',
        ]);

        Livewire::actingAs($coordinator)
            ->test(SettingIndex::class)
            ->call('resetUserPassword', $targetUser->id)
            ->assertDispatched('toast', ['msg' => 'Only administrators can reset user credentials.', 'type' => 'err']);
    }

    public function test_notification_click_redirects_to_settings_with_highlighted_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        $user = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
        ]);

        $notification = Notification::create([
            'user_id' => $admin->id,
            'type' => 'auth',
            'title' => 'Password Reset Requested',
            'body' => "{$user->name} requested a password reset.",
            'link_type' => 'settings',
            'link_id' => $user->id,
            'is_read' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(NotificationDrawer::class)
            ->call('openNotification', $notification->id)
            ->assertRedirect(route('settings.index', ['highlight_user' => $user->id]));

        $this->assertTrue($notification->fresh()->is_read);
    }
}
