<?php

namespace Tests\Feature\Auth;

use App\Livewire\Components\NotificationDrawer;
use App\Livewire\Notifications\NotificationDetail;
use App\Livewire\Notifications\NotificationIndex;
use App\Livewire\Settings\SettingIndex;
use App\Mail\TemporaryPasswordMail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    public function test_admin_forgot_password_sends_email_and_sets_temporary_credentials(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'email' => 'admin@the10xagency.in',
            'must_change' => false,
        ]);

        $response = $this->post('/forgot-password', ['email' => $admin->email]);

        $response->assertSessionHas('status', 'A temporary password has been emailed to you. Please check your inbox to sign in.');

        $admin->refresh();
        $this->assertTrue($admin->must_change);
        $this->assertSame(0, $admin->fail_count);
        $this->assertNull($admin->locked_until);

        Mail::assertSent(TemporaryPasswordMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });

        $this->assertDatabaseHas('password_reset_requests', [
            'user_id' => $admin->id,
            'status' => 'Resolved',
        ]);
    }

    public function test_manager_forgot_password_sends_email_to_manager_and_active_admins(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'email' => 'admin1@the10xagency.in',
        ]);

        $manager = User::factory()->create([
            'role' => 'Manager',
            'status' => 'Active',
            'email' => 'manager@the10xagency.in',
            'must_change' => false,
        ]);

        $response = $this->post('/forgot-password', ['email' => $manager->email]);

        $response->assertSessionHas('status', 'A temporary password has been emailed to you and your administrator. Please check your inbox to sign in.');

        $manager->refresh();
        $this->assertTrue($manager->must_change);

        // Verify sent to manager
        Mail::assertSent(TemporaryPasswordMail::class, function ($mail) use ($manager) {
            return $mail->hasTo($manager->email);
        });

        // Verify sent to admin
        Mail::assertSent(TemporaryPasswordMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'title' => 'Manager Password Reset',
        ]);
    }

    public function test_coordinator_and_sales_forgot_password_does_not_send_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        $manager = User::factory()->create([
            'role' => 'Manager',
            'status' => 'Active',
        ]);

        $coordinator = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
            'email' => 'coordinator@the10xagency.in',
            'reporting_to' => $manager->id,
            'must_change' => false,
        ]);

        $response = $this->post('/forgot-password', ['email' => $coordinator->email]);

        $response->assertSessionHas('status', 'Password reset request has been logged. Your manager or administrator will issue your temporary credentials.');

        // NO email must be sent
        Mail::assertNothingSent();

        // Must change remains false until admin/manager issues temporary password
        $this->assertFalse($coordinator->fresh()->must_change);

        $this->assertDatabaseHas('password_reset_requests', [
            'user_id' => $coordinator->id,
            'status' => 'Pending',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'title' => 'Password Reset Requested',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $manager->id,
            'title' => 'Team Password Reset Request',
        ]);
    }

    public function test_inactive_user_cannot_request_password_reset(): void
    {
        Mail::fake();

        $inactiveAdmin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Inactive',
            'email' => 'inactive@the10xagency.in',
        ]);

        $response = $this->post('/forgot-password', ['email' => $inactiveAdmin->email]);

        $response->assertSessionHasErrors(['email']);
        Mail::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_requests', [
            'user_id' => $inactiveAdmin->id,
        ]);
    }

    public function test_admin_cannot_reset_password_for_inactive_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        $inactiveUser = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Inactive',
        ]);

        Livewire::actingAs($admin)
            ->test(SettingIndex::class)
            ->call('resetUserPassword', $inactiveUser->id)
            ->assertDispatched('toast', ['msg' => 'Cannot reset credentials for an inactive user. Activate the account first.', 'type' => 'err']);
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
            ->assertForbidden();
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

    public function test_notification_redirects_to_preview_when_user_lacks_module_permission(): void
    {
        $salesUser = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Active',
        ]);

        // 1. Notification pointing to settings (Sales has no permission to view settings)
        $restrictedNotif = Notification::create([
            'user_id' => $salesUser->id,
            'type' => 'auth',
            'title' => 'System Alert',
            'body' => 'Account policy updated.',
            'link_type' => 'settings',
            'link_id' => 1,
            'is_read' => false,
        ]);

        // Clicking in NotificationDrawer redirects to notification preview instead of /settings (403)
        Livewire::actingAs($salesUser)
            ->test(NotificationDrawer::class)
            ->call('openNotification', $restrictedNotif->id)
            ->assertRedirect(route('notifications.show', $restrictedNotif->id));

        $this->assertTrue($restrictedNotif->fresh()->is_read);

        // Clicking in NotificationIndex also redirects to notification preview
        Livewire::actingAs($salesUser)
            ->test(NotificationIndex::class)
            ->call('openNotification', $restrictedNotif->id)
            ->assertRedirect(route('notifications.show', $restrictedNotif->id));

        // Visiting NotificationDetail for this restricted notification prevents navigation to target
        Livewire::actingAs($salesUser)
            ->test(NotificationDetail::class, ['notification' => $restrictedNotif])
            ->call('navigateToTarget')
            ->assertDispatched('toast');
    }
}
