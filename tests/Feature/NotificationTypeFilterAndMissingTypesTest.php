<?php

namespace Tests\Feature;

use App\Livewire\Components\NotificationDrawer;
use App\Livewire\Notifications\NotificationIndex;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationTypeFilterAndMissingTypesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $coordinator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'email' => 'admin-notif@test.com',
            'must_change' => false,
        ]);

        $this->manager = User::factory()->create([
            'role' => 'Manager',
            'status' => 'Active',
            'email' => 'manager-notif@test.com',
            'reporting_to' => $this->admin->id,
            'must_change' => false,
        ]);

        $this->coordinator = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
            'email' => 'coord-notif@test.com',
            'reporting_to' => $this->manager->id,
            'must_change' => false,
        ]);
    }

    public function test_filtering_by_password_reset_returns_all_security_notifications(): void
    {
        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Password Reset Requested',
            'title' => 'Password Reset Requested',
            'body' => 'Coordinator requested password reset',
        ]);

        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Manager Password Reset',
            'title' => 'Manager Password Reset Alert',
            'body' => 'Manager requested password reset',
        ]);

        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Account Locked Alert',
            'title' => 'Account Locked: User',
            'body' => 'Account locked due to 5 failed attempts',
        ]);

        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Payment Received',
            'title' => 'Payment Received: RCP-0001',
            'body' => 'Received ₹50,000',
        ]);

        Livewire::actingAs($this->admin)
            ->test(NotificationIndex::class)
            ->set('type', 'password_reset')
            ->assertSee('Password Reset Requested')
            ->assertSee('Manager Password Reset Alert')
            ->assertSee('Account Locked: User')
            ->assertDontSee('Payment Received: RCP-0001');
    }

    public function test_filtering_by_payment_returns_all_payment_notifications(): void
    {
        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Payment Received',
            'title' => 'Payment Received: RCP-0002',
            'body' => 'Received ₹100,000',
        ]);

        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'payment_due',
            'title' => 'Payment Due: Installment 1',
            'body' => 'Installment due in 3 days',
        ]);

        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Project Approved',
            'title' => 'Project Approved: PJT-0001',
            'body' => 'Project was approved',
        ]);

        Livewire::actingAs($this->admin)
            ->test(NotificationIndex::class)
            ->set('type', 'payment')
            ->assertSee('Payment Received: RCP-0002')
            ->assertSee('Payment Due: Installment 1')
            ->assertDontSee('Project Approved: PJT-0001');
    }

    public function test_filtering_by_milestone_returns_all_milestone_notifications(): void
    {
        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Milestone Completed',
            'title' => 'Milestone Completed: UAT Sign-off',
            'body' => 'Milestone completed on time',
        ]);

        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Milestones Updated',
            'title' => 'Milestones Updated: Project Alpha',
            'body' => 'Milestones adjusted',
        ]);

        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Customer Created',
            'title' => 'Customer Created: Acme Corp',
            'body' => 'New customer registered',
        ]);

        Livewire::actingAs($this->admin)
            ->test(NotificationIndex::class)
            ->set('type', 'milestone')
            ->assertSee('Milestone Completed: UAT Sign-off')
            ->assertSee('Milestones Updated: Project Alpha')
            ->assertDontSee('Customer Created: Acme Corp');
    }

    public function test_filtering_by_project_returns_all_project_notifications(): void
    {
        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Project Created',
            'title' => 'Project Created: PJT-0010',
            'body' => 'New project',
        ]);

        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Project Health Alert',
            'title' => 'Project Health Alert: Delayed',
            'body' => 'Project delayed',
        ]);

        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Task Assigned',
            'title' => 'Task Assigned: Prepare Scope',
            'body' => 'Task assigned',
        ]);

        Livewire::actingAs($this->admin)
            ->test(NotificationIndex::class)
            ->set('type', 'project')
            ->assertSee('Project Created: PJT-0010')
            ->assertSee('Project Health Alert: Delayed')
            ->assertDontSee('Task Assigned: Prepare Scope');
    }

    public function test_filtering_by_customer_returns_all_customer_notifications(): void
    {
        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Customer Approved',
            'title' => 'Customer Approved: CUS-0010',
            'body' => 'Customer approved',
        ]);

        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Customer Rejected',
            'title' => 'Customer Rejected: CUS-0011',
            'body' => 'Customer rejected with feedback',
        ]);

        Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Follow-up Logged',
            'title' => 'Follow-up Logged: Phone Call',
            'body' => 'Client call summary',
        ]);

        Livewire::actingAs($this->admin)
            ->test(NotificationIndex::class)
            ->set('type', 'customer')
            ->assertSee('Customer Approved: CUS-0010')
            ->assertSee('Customer Rejected: CUS-0011')
            ->assertDontSee('Follow-up Logged: Phone Call');
    }

    public function test_password_reset_request_creates_notifications_for_admin_manager_and_requesting_user(): void
    {
        $response = $this->post('/forgot-password', ['email' => $this->coordinator->email]);

        $response->assertSessionHas('status');

        // Admin receives in-app notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'type' => 'Password Reset Requested',
        ]);

        // Manager receives in-app notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->manager->id,
            'type' => 'Password Reset Requested',
        ]);

        // Requesting Coordinator also receives confirmation in-app notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->coordinator->id,
            'type' => 'Password Reset Requested',
        ]);
    }

    public function test_admin_password_reset_request_creates_in_app_notifications(): void
    {
        $response = $this->post('/forgot-password', ['email' => $this->admin->email]);

        $response->assertSessionHas('status');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'type' => 'Password Reset Requested',
        ]);
    }

    public function test_notification_category_and_kind_config_accessors(): void
    {
        $notif = Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Password Reset Requested',
            'title' => 'Password Reset Requested',
            'body' => 'Coordinator requested password reset',
        ]);

        $this->assertSame('password_reset', $notif->category);
        $config = $notif->kind_config;
        $this->assertSame('lock', $config['icon']);
        $this->assertSame('Password Reset', $config['badge']);
    }

    public function test_notification_drawer_redirects_for_all_link_types(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-NOTIF-1',
            'contact_name' => 'Drawer Test Customer',
            'phone' => '9876543210',
            'state' => 'MH',
            'status' => 'Active',
            'sales_person_id' => $this->admin->id,
            'created_by' => $this->admin->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-NOTIF-1',
            'customer_id' => $customer->id,
            'name' => 'Drawer Test Project',
            'value' => 50000,
            'cost' => 20000,
            'status' => 'In Progress',
            'health' => 'On Track',
            'coordinator_id' => $this->admin->id,
            'start_date' => now()->toDateString(),
        ]);

        // 1. Team redirect
        $teamNotif = Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Team Member Assigned',
            'title' => 'Team Update',
            'body' => 'New team member',
            'link_type' => 'team',
            'link_id' => $this->coordinator->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(NotificationDrawer::class)
            ->call('openNotification', $teamNotif->id)
            ->assertRedirect(route('team.index'));

        // 2. Project redirect
        $projectNotif = Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Project Created',
            'title' => 'Project Created',
            'body' => 'New project',
            'link_type' => 'project',
            'link_id' => $project->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(NotificationDrawer::class)
            ->call('openNotification', $projectNotif->id)
            ->assertRedirect(route('projects.show', $project->id));

        // 3. Customer redirect
        $customerNotif = Notification::create([
            'user_id' => $this->admin->id,
            'type' => 'Customer Created',
            'title' => 'Customer Created',
            'body' => 'New customer',
            'link_type' => 'customer',
            'link_id' => $customer->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(NotificationDrawer::class)
            ->call('openNotification', $customerNotif->id)
            ->assertRedirect(route('customers.show', $customer->id));
    }

    public function test_clearing_filters_resets_type_status_and_search(): void
    {
        Livewire::actingAs($this->admin)
            ->test(NotificationIndex::class)
            ->set('type', 'password_reset')
            ->set('status', 'unread')
            ->set('search', 'reset')
            ->call('clearFilters')
            ->assertSet('type', '')
            ->assertSet('status', '')
            ->assertSet('search', '');
    }
}
