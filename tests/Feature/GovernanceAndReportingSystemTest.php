<?php

namespace Tests\Feature;

use App\Livewire\Customers\CustomerModal;
use App\Livewire\Payments\PaymentModal;
use App\Livewire\Projects\ProjectModal;
use App\Livewire\Settings\SettingIndex;
use App\Livewire\Settings\UserModal;
use App\Mail\TemporaryPasswordMail;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\PasswordResetRequest;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class GovernanceAndReportingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $coordinator;

    protected User $sales;

    protected Customer $customer;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'must_change' => false,
        ]);

        $this->manager = User::factory()->create([
            'role' => 'Manager',
            'status' => 'Active',
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'reporting_to' => $this->admin->id,
            'must_change' => false,
        ]);

        $this->coordinator = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
            'name' => 'Coordinator User',
            'email' => 'coord@test.com',
            'reporting_to' => $this->manager->id,
            'must_change' => false,
        ]);

        $this->sales = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Active',
            'name' => 'Sales Rep',
            'email' => 'sales@test.com',
            'reporting_to' => $this->coordinator->id,
            'target' => 500000.00,
            'must_change' => false,
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUS-TEST-01',
            'company' => 'Acme Global',
            'contact_name' => 'John Doe',
            'phone' => '9988776655',
            'state' => 'MH',
            'sales_person_id' => $this->sales->id,
            'status' => 'Active',
            'created_by' => $this->sales->id,
        ]);

        $this->project = Project::create([
            'project_code' => 'PJT-TEST-01',
            'name' => 'Enterprise CRM Portal',
            'customer_id' => $this->customer->id,
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
            'value' => 120000.00,
            'cost' => 40000.00,
            'health' => 'On Track',
            'status' => 'In Progress',
            'start_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(20)->toDateString(),
            'created_by' => $this->coordinator->id,
        ]);
    }

    public function test_updating_project_logs_structured_field_changes_diff(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectModal::class)
            ->call('openModal', ['id' => $this->project->id])
            ->set('health', 'Delayed')
            ->set('delay_reason', 'Client delayed milestone approval')
            ->set('value', 150000.00)
            ->call('save');

        $this->project->refresh();
        $this->assertEquals('Delayed', $this->project->health);
        $this->assertEquals(150000.00, (float) $this->project->value);

        $log = ActivityLog::where('entity', 'Project')
            ->where('entity_id', $this->project->id)
            ->where('action', 'Updated Project')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertIsArray($log->changes);
        $this->assertArrayHasKey('health', $log->changes);
        $this->assertEquals('On Track', $log->changes['health']['old']);
        $this->assertEquals('Delayed', $log->changes['health']['new']);
        $this->assertArrayHasKey('value', $log->changes);
    }

    public function test_updating_customer_logs_structured_field_changes_diff(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CustomerModal::class)
            ->call('openModal', ['id' => $this->customer->id])
            ->set('company', 'Acme Global Holdings')
            ->set('phone', '9123456789')
            ->call('save');

        $this->customer->refresh();
        $this->assertEquals('Acme Global Holdings', $this->customer->company);

        $log = ActivityLog::where('entity', 'Customer')
            ->where('entity_id', $this->customer->id)
            ->where('action', 'Updated Customer')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertIsArray($log->changes);
        $this->assertArrayHasKey('company', $log->changes);
        $this->assertEquals('Acme Global', $log->changes['company']['old']);
        $this->assertEquals('Acme Global Holdings', $log->changes['company']['new']);
    }

    public function test_project_health_alert_notifies_coordinator_and_admins(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectModal::class)
            ->call('openModal', ['id' => $this->project->id])
            ->set('health', 'Delayed')
            ->set('delay_reason', 'Third-party API downtime')
            ->call('save');

        $coordNotif = Notification::where('user_id', $this->coordinator->id)
            ->where('type', 'Project Health Alert')
            ->latest('id')
            ->first();

        $this->assertNotNull($coordNotif);
        $this->assertStringContainsString('Delayed', $coordNotif->body);
    }

    public function test_recording_payment_dispatches_notifications_to_sales_coordinator_and_admin(): void
    {
        $term = PaymentTerm::create([
            'project_id' => $this->project->id,
            'label' => 'Advance Deposit',
            'amount' => 50000.00,
            'received' => 0.00,
            'tds' => 0.00,
            'status' => 'Pending',
            'seq' => 1,
            'percent' => 50.00,
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        Livewire::actingAs($this->coordinator)
            ->test(PaymentModal::class)
            ->call('openModal', ['projectId' => $this->project->id, 'termId' => $term->id])
            ->set('amount', 50000.00)
            ->set('mode', 'NEFT')
            ->set('reference', 'UTR99887711')
            ->call('save');

        // Salesperson notification
        $salesNotif = Notification::where('user_id', $this->sales->id)
            ->where('type', 'Payment Received')
            ->first();
        $this->assertNotNull($salesNotif);
        $this->assertStringContainsString('50,000', $salesNotif->body);

        // Admin notification
        $adminNotif = Notification::where('user_id', $this->admin->id)
            ->where('type', 'Payment Received')
            ->first();
        $this->assertNotNull($adminNotif);
    }

    public function test_inactive_user_cannot_submit_password_reset_request(): void
    {
        $inactiveUser = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Inactive',
            'email' => 'inactive@test.com',
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'inactive@test.com',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('password_reset_requests', [
            'user_id' => $inactiveUser->id,
        ]);
    }

    public function test_active_user_forgot_password_request_creates_pending_record_and_notifies_management(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => $this->sales->email,
        ]);

        $response->assertSessionHas('status');

        $this->assertDatabaseHas('password_reset_requests', [
            'user_id' => $this->sales->id,
            'status' => 'Pending',
        ]);

        // In-app notification to Admin
        $adminNotif = Notification::where('user_id', $this->admin->id)
            ->where('type', 'Password Reset Requested')
            ->latest('id')
            ->first();
        $this->assertNotNull($adminNotif);
    }

    public function test_admin_can_resolve_password_reset_request_with_secure_random_password(): void
    {
        Mail::fake();

        // Create pending request for manager
        PasswordResetRequest::create([
            'user_id' => $this->manager->id,
            'status' => 'Pending',
        ]);

        Livewire::actingAs($this->admin)
            ->test(SettingIndex::class)
            ->call('resetUserPassword', $this->manager->id);

        $this->manager->refresh();
        $this->assertTrue($this->manager->must_change);

        // Pending request marked as resolved
        $this->assertDatabaseHas('password_reset_requests', [
            'user_id' => $this->manager->id,
            'status' => 'Resolved',
            'resolved_by' => $this->admin->id,
        ]);

        // Email dispatched to manager and admin
        Mail::assertSent(TemporaryPasswordMail::class);
    }

    public function test_coordinator_or_sales_reset_does_not_send_email(): void
    {
        Mail::fake();

        PasswordResetRequest::create([
            'user_id' => $this->sales->id,
            'status' => 'Pending',
        ]);

        Livewire::actingAs($this->admin)
            ->test(SettingIndex::class)
            ->call('resetUserPassword', $this->sales->id);

        // Password reset for Sales must NOT dispatch email (in-app handoff only)
        Mail::assertNothingSent();

        $this->sales->refresh();
        $this->assertTrue($this->sales->must_change);
    }

    public function test_reports_module_is_accessible_to_all_authenticated_roles_with_role_scoping(): void
    {
        // Admin
        $adminResp = $this->actingAs($this->admin)->get('/reports');
        $adminResp->assertStatus(200);
        $adminResp->assertSee('Payment Ageing Analysis');
        $adminResp->assertSee('Sales Attribution &amp; Performance', false);

        // Manager
        $mgrResp = $this->actingAs($this->manager)->get('/reports');
        $mgrResp->assertStatus(200);
        $mgrResp->assertSee('Team Bookings');
        $mgrResp->assertSee('Team Coordinator Velocity');

        // Coordinator
        $coordResp = $this->actingAs($this->coordinator)->get('/reports');
        $coordResp->assertStatus(200);
        $coordResp->assertSee('Assigned Projects');
        $coordResp->assertSee('Upcoming Project Deliveries (Next 60 Days)');
        $coordResp->assertDontSee('Sales Attribution &amp; Performance', false);

        // Sales
        $salesResp = $this->actingAs($this->sales)->get('/reports');
        $salesResp->assertStatus(200);
        $salesResp->assertSee('Total Closed Deals');
        $salesResp->assertSee('Deals Pipeline &amp; Customer Realization', false);
        $salesResp->assertDontSee('Payment Ageing Analysis');
    }

    public function test_custom_user_permissions_override_role_defaults(): void
    {
        // Default Coordinator cannot delete projects
        $this->assertFalse($this->coordinator->hasPermission('projects', 'delete'));

        // Admin grants custom project deletion permission via UserModal
        Livewire::actingAs($this->admin)
            ->test(UserModal::class)
            ->call('openModal', ['id' => $this->coordinator->id])
            ->call('togglePermission', 'projects', 'delete')
            ->call('save');

        $this->coordinator->refresh();
        $this->assertNotNull($this->coordinator->permissions);
        $this->assertTrue($this->coordinator->hasPermission('projects', 'delete'));
    }
}
