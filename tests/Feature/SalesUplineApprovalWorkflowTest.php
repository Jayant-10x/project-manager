<?php

namespace Tests\Feature;

use App\Livewire\Customers\CustomerDetail;
use App\Livewire\Customers\CustomerIndex;
use App\Livewire\Customers\CustomerModal;
use App\Livewire\Projects\ProjectDetail;
use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Projects\ProjectModal;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesUplineApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $otherManager;

    protected User $sales;

    protected User $coordinator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Super Admin',
            'role' => 'Admin',
            'status' => 'Active',
            'must_change' => false,
        ]);

        $this->manager = User::factory()->create([
            'name' => 'Sales Manager',
            'role' => 'Manager',
            'status' => 'Active',
            'reporting_to' => $this->admin->id,
            'must_change' => false,
        ]);

        $this->otherManager = User::factory()->create([
            'name' => 'Other Branch Manager',
            'role' => 'Manager',
            'status' => 'Active',
            'reporting_to' => $this->admin->id,
            'must_change' => false,
        ]);

        $this->sales = User::factory()->create([
            'name' => 'Field Sales Rep',
            'role' => 'Sales',
            'status' => 'Active',
            'reporting_to' => $this->manager->id,
            'must_change' => false,
        ]);

        $this->coordinator = User::factory()->create([
            'name' => 'Project Coordinator',
            'role' => 'Coordinator',
            'status' => 'Active',
            'reporting_to' => $this->manager->id,
            'must_change' => false,
        ]);
    }

    public function test_salesperson_creating_customer_flags_pending_and_notifies_uplines(): void
    {
        $this->actingAs($this->sales);

        Livewire::test(CustomerModal::class)
            ->call('openModal')
            ->set('company', 'Apex Global Corp')
            ->set('contact_name', 'Vikram Seth')
            ->set('phone', '9876543210')
            ->set('state', 'MH')
            ->set('status', 'Active')
            ->call('save')
            ->assertHasNoErrors();

        $customer = Customer::where('company', 'Apex Global Corp')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Pending', $customer->approval_status);
        $this->assertTrue($customer->isPendingApproval());
        $this->assertFalse($customer->isApproved());
        $this->assertEquals($this->sales->id, $customer->sales_person_id);
        $this->assertEquals($this->sales->id, $customer->created_by);

        // Upline manager and admin should have received notification
        $managerNotification = Notification::where('user_id', $this->manager->id)
            ->where('type', 'Customer Approval Required')
            ->first();
        $this->assertNotNull($managerNotification);
        $this->assertStringContainsString('Apex Global Corp', $managerNotification->title);

        $adminNotification = Notification::where('user_id', $this->admin->id)
            ->where('type', 'Customer Approval Required')
            ->first();
        $this->assertNotNull($adminNotification);

        // Unrelated manager should NOT have received notification
        $otherNotification = Notification::where('user_id', $this->otherManager->id)
            ->where('type', 'Customer Approval Required')
            ->first();
        $this->assertNull($otherNotification);

        // Activity log should exist for Submitted Customer
        $log = ActivityLog::where('action', 'Submitted Customer')
            ->where('user_id', $this->sales->id)
            ->where('entity_id', $customer->id)
            ->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('submitted customer Apex Global Corp for approval', $log->detail);
    }

    public function test_upline_manager_approves_customer(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-TEST-01',
            'company' => 'Blue Peak Labs',
            'contact_name' => 'Aditi Rao',
            'phone' => '9812345678',
            'state' => 'KA',
            'status' => 'Active',
            'approval_status' => 'Pending',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $this->assertTrue($customer->canBeApprovedBy($this->manager));
        $this->assertTrue($customer->canBeApprovedBy($this->admin));
        $this->assertTrue($customer->canBeApprovedBy($this->coordinator));
        $this->assertTrue($customer->canBeApprovedBy($this->otherManager));
        $this->assertFalse($customer->canBeApprovedBy($this->sales));

        // Manager approves via CustomerDetail
        $this->actingAs($this->manager);

        Livewire::test(CustomerDetail::class, ['customer' => $customer])
            ->call('approveCustomer')
            ->assertHasNoErrors();

        $customer->refresh();
        $this->assertEquals('Approved', $customer->approval_status);
        $this->assertTrue($customer->isApproved());
        $this->assertFalse($customer->isPendingApproval());
        $this->assertEquals($this->manager->id, $customer->approved_by);
        $this->assertNotNull($customer->approved_at);

        // Salesperson received notification
        $salesNotification = Notification::where('user_id', $this->sales->id)
            ->where('type', 'Customer Approved')
            ->first();
        $this->assertNotNull($salesNotification);
        $this->assertStringContainsString('approved by Sales Manager', $salesNotification->body);

        // Activity log
        $log = ActivityLog::where('action', 'Approved Customer')
            ->where('user_id', $this->manager->id)
            ->where('entity_id', $customer->id)
            ->first();
        $this->assertNotNull($log);
    }

    public function test_upline_manager_rejects_customer(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-TEST-02',
            'company' => 'Starlight Media',
            'contact_name' => 'Karan Johar',
            'phone' => '9899887766',
            'state' => 'DL',
            'status' => 'Active',
            'approval_status' => 'Pending',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $this->actingAs($this->manager);

        Livewire::test(CustomerDetail::class, ['customer' => $customer])
            ->call('rejectCustomer', 'Missing valid tax documents and company authorization letter')
            ->assertHasNoErrors();

        $customer->refresh();
        $this->assertEquals('Rejected', $customer->approval_status);
        $this->assertTrue($customer->isRejected());
        $this->assertEquals($this->manager->id, $customer->approved_by);
        $this->assertEquals('Missing valid tax documents and company authorization letter', $customer->rejection_reason);

        // Salesperson received rejection notification
        $salesNotification = Notification::where('user_id', $this->sales->id)
            ->where('type', 'Customer Rejected')
            ->first();
        $this->assertNotNull($salesNotification);
        $this->assertStringContainsString('Missing valid tax documents', $salesNotification->body);

        // Activity log
        $log = ActivityLog::where('action', 'Rejected Customer')
            ->where('user_id', $this->manager->id)
            ->where('entity_id', $customer->id)
            ->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Missing valid tax documents', $log->detail);
    }

    public function test_salesperson_cannot_approve_their_own_customer(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-TEST-03',
            'company' => 'Self Approvable Test',
            'contact_name' => 'Self Tester',
            'phone' => '9876500000',
            'state' => 'MH',
            'status' => 'Active',
            'approval_status' => 'Pending',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $this->assertFalse($customer->canBeApprovedBy($this->sales));

        $this->actingAs($this->sales);

        Livewire::test(CustomerDetail::class, ['customer' => $customer])
            ->call('approveCustomer')
            ->assertStatus(403);

        $customer->refresh();
        $this->assertEquals('Pending', $customer->approval_status);
    }

    public function test_salesperson_creating_project_flags_pending_and_notifies_uplines(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-TEST-04',
            'company' => 'Solaris Technologies',
            'contact_name' => 'Sunil Verma',
            'phone' => '9822334455',
            'state' => 'KA',
            'status' => 'Active',
            'approval_status' => 'Approved',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->sales);

        Livewire::test(ProjectModal::class)
            ->call('openModal')
            ->set('name', 'Solaris Cloud Portal')
            ->set('customer_id', $customer->id)
            ->set('project_type', 'Web')
            ->set('value', 250000)
            ->set('start_date', now()->toDateString())
            ->set('status', 'In Progress')
            ->call('save')
            ->assertHasNoErrors();

        $project = Project::where('name', 'Solaris Cloud Portal')->first();
        $this->assertNotNull($project);
        $this->assertEquals('Pending', $project->approval_status);
        $this->assertTrue($project->isPendingApproval());
        $this->assertEquals($this->sales->id, $project->sales_person_id);
        $this->assertEquals($this->sales->id, $project->created_by);

        // Upline notification sent
        $managerNotification = Notification::where('user_id', $this->manager->id)
            ->where('type', 'Project Approval Required')
            ->first();
        $this->assertNotNull($managerNotification);
        $this->assertStringContainsString('Solaris Cloud Portal', $managerNotification->title);

        $adminNotification = Notification::where('user_id', $this->admin->id)
            ->where('type', 'Project Approval Required')
            ->first();
        $this->assertNotNull($adminNotification);

        // Activity log
        $log = ActivityLog::where('action', 'Submitted Project')
            ->where('user_id', $this->sales->id)
            ->where('entity_id', $project->id)
            ->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('submitted project Solaris Cloud Portal for approval', $log->detail);
    }

    public function test_admin_approves_project(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-TEST-05',
            'company' => 'Quantum Tech',
            'contact_name' => 'Rohit Roy',
            'phone' => '9811223344',
            'state' => 'DL',
            'status' => 'Active',
            'approval_status' => 'Approved',
        ]);

        $project = Project::create([
            'project_code' => 'PJT-TEST-01',
            'name' => 'Quantum CRM Platform',
            'customer_id' => $customer->id,
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'start_date' => now()->toDateString(),
            'status' => 'In Progress',
            'approval_status' => 'Pending',
            'value' => 150000,
        ]);

        $this->assertTrue($project->canBeApprovedBy($this->admin));
        $this->assertTrue($project->canBeApprovedBy($this->manager));
        $this->assertFalse($project->canBeApprovedBy($this->sales));

        $this->actingAs($this->admin);

        Livewire::test(ProjectDetail::class, ['project' => $project])
            ->call('approveProject')
            ->assertHasNoErrors();

        $project->refresh();
        $this->assertEquals('Approved', $project->approval_status);
        $this->assertTrue($project->isApproved());
        $this->assertEquals($this->admin->id, $project->approved_by);
        $this->assertNotNull($project->approved_at);

        // Salesperson received notification
        $salesNotification = Notification::where('user_id', $this->sales->id)
            ->where('type', 'Project Approved')
            ->first();
        $this->assertNotNull($salesNotification);
        $this->assertStringContainsString('approved by Super Admin', $salesNotification->body);

        // Activity log
        $log = ActivityLog::where('action', 'Approved Project')
            ->where('user_id', $this->admin->id)
            ->where('entity_id', $project->id)
            ->first();
        $this->assertNotNull($log);
    }

    public function test_admin_rejects_project(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-TEST-06',
            'company' => 'Matrix Net',
            'contact_name' => 'Neha Gupta',
            'phone' => '9833445566',
            'state' => 'DL',
            'status' => 'Active',
            'approval_status' => 'Approved',
        ]);

        $project = Project::create([
            'project_code' => 'PJT-TEST-02',
            'name' => 'Matrix App V1',
            'customer_id' => $customer->id,
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'start_date' => now()->toDateString(),
            'status' => 'In Progress',
            'approval_status' => 'Pending',
            'value' => 80000,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ProjectDetail::class, ['project' => $project])
            ->call('rejectProject', 'Contract scope is insufficient')
            ->assertHasNoErrors();

        $project->refresh();
        $this->assertEquals('Rejected', $project->approval_status);
        $this->assertTrue($project->isRejected());
        $this->assertEquals($this->admin->id, $project->approved_by);
        $this->assertEquals('Contract scope is insufficient', $project->rejection_reason);

        // Notification
        $salesNotification = Notification::where('user_id', $this->sales->id)
            ->where('type', 'Project Rejected')
            ->first();
        $this->assertNotNull($salesNotification);
        $this->assertStringContainsString('Contract scope is insufficient', $salesNotification->body);

        // Activity log
        $log = ActivityLog::where('action', 'Rejected Project')
            ->where('user_id', $this->admin->id)
            ->where('entity_id', $project->id)
            ->first();
        $this->assertNotNull($log);
    }

    public function test_admin_creating_customer_or_project_defaults_to_approved(): void
    {
        $this->actingAs($this->admin);

        // Customer
        Livewire::test(CustomerModal::class)
            ->call('openModal')
            ->set('company', 'Executive Enterprise')
            ->set('contact_name', 'Rahul Bose')
            ->set('phone', '9898989898')
            ->set('state', 'DL')
            ->set('sales_person_id', $this->sales->id)
            ->set('status', 'Active')
            ->call('save')
            ->assertHasNoErrors();

        $customer = Customer::where('company', 'Executive Enterprise')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Approved', $customer->approval_status);
        $this->assertTrue($customer->isApproved());

        // Project
        Livewire::test(ProjectModal::class)
            ->call('openModal')
            ->set('name', 'Executive Portal')
            ->set('customer_id', $customer->id)
            ->set('coordinator_id', $this->coordinator->id)
            ->set('sales_person_id', $this->sales->id)
            ->set('project_type', 'Web')
            ->set('value', 300000)
            ->set('start_date', now()->toDateString())
            ->set('status', 'In Progress')
            ->call('save')
            ->assertHasNoErrors();

        $project = Project::where('name', 'Executive Portal')->first();
        $this->assertNotNull($project);
        $this->assertEquals('Approved', $project->approval_status);
        $this->assertTrue($project->isApproved());
    }

    public function test_index_quick_approval_by_manager(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-TEST-07',
            'company' => 'Quick Approve Co',
            'contact_name' => 'Pooja Bhatt',
            'phone' => '9877112233',
            'state' => 'MH',
            'status' => 'Active',
            'approval_status' => 'Pending',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-TEST-03',
            'name' => 'Quick Project',
            'customer_id' => $customer->id,
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'start_date' => now()->toDateString(),
            'status' => 'In Progress',
            'approval_status' => 'Pending',
            'value' => 50000,
        ]);

        $this->actingAs($this->manager);

        // Approve Customer via CustomerIndex
        Livewire::test(CustomerIndex::class)
            ->call('approveCustomer', $customer->id)
            ->assertHasNoErrors();

        $this->assertTrue($customer->fresh()->isApproved());

        // Approve Project via ProjectIndex
        Livewire::test(ProjectIndex::class)
            ->call('approveProject', $project->id)
            ->assertHasNoErrors();

        $this->assertTrue($project->fresh()->isApproved());
    }

    public function test_salesperson_editing_rejected_customer_resubmits_for_approval_with_logs_and_notifications(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-REJ-01',
            'company' => 'Initial Rejected Co',
            'contact_name' => 'Amit Sharma',
            'phone' => '9876543210',
            'state' => 'MH',
            'status' => 'Active',
            'approval_status' => 'Rejected',
            'rejection_reason' => 'Contact details inaccurate',
            'approved_by' => $this->manager->id,
            'approved_at' => now()->subDay(),
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $this->actingAs($this->sales);

        Livewire::test(CustomerModal::class)
            ->call('loadCustomer', $customer->id)
            ->assertSet('isRejected', true)
            ->set('phone', '9811223344')
            ->set('contact_name', 'Amit K. Sharma')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('customer-saved')
            ->assertDispatched('toast');

        $fresh = $customer->fresh();
        $this->assertEquals('Pending', $fresh->approval_status);
        $this->assertTrue($fresh->isPendingApproval());
        $this->assertNull($fresh->rejection_reason);
        $this->assertNull($fresh->approved_by);
        $this->assertNull($fresh->approved_at);
        $this->assertEquals('9811223344', $fresh->phone);

        // Assert ActivityLog
        $log = ActivityLog::where('entity', 'Customer')
            ->where('entity_id', $customer->id)
            ->where('action', 'Resubmitted Customer')
            ->latest('id')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals($this->sales->id, $log->user_id);
        $this->assertStringContainsString('updated and resubmitted customer', $log->detail);

        // Assert Upline notification sent to manager
        $notification = Notification::where('user_id', $this->manager->id)
            ->where('type', 'Customer Approval Required')
            ->where('link_id', $customer->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Resubmitted for Approval', $notification->title);
    }

    public function test_salesperson_editing_rejected_project_resubmits_for_approval_with_logs_and_notifications(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-APP-02',
            'company' => 'Approved Customer Co',
            'contact_name' => 'Vikas Roy',
            'phone' => '9870001122',
            'state' => 'DL',
            'status' => 'Active',
            'approval_status' => 'Approved',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-REJ-01',
            'name' => 'Rejected Campaign Web',
            'customer_id' => $customer->id,
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'start_date' => now()->toDateString(),
            'status' => 'Onboarding',
            'approval_status' => 'Rejected',
            'rejection_reason' => 'Cost calculation missing deliverables',
            'approved_by' => $this->manager->id,
            'approved_at' => now()->subDay(),
            'value' => 75000,
            'cost' => 30000,
        ]);

        $this->actingAs($this->sales);

        Livewire::test(ProjectModal::class)
            ->call('loadProject', $project->id)
            ->assertSet('isRejected', true)
            ->set('value', 85000)
            ->set('cost', 35000)
            ->set('name', 'Revised Campaign Web')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('project-saved')
            ->assertDispatched('toast');

        $fresh = $project->fresh();
        $this->assertEquals('Pending', $fresh->approval_status);
        $this->assertTrue($fresh->isPendingApproval());
        $this->assertNull($fresh->rejection_reason);
        $this->assertNull($fresh->approved_by);
        $this->assertNull($fresh->approved_at);
        $this->assertEquals(85000, (float) $fresh->value);
        $this->assertEquals('Revised Campaign Web', $fresh->name);

        // Assert ActivityLog
        $log = ActivityLog::where('entity', 'Project')
            ->where('entity_id', $project->id)
            ->where('action', 'Resubmitted Project')
            ->latest('id')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals($this->sales->id, $log->user_id);
        $this->assertStringContainsString('updated and resubmitted project', $log->detail);

        // Assert Upline notification sent to manager
        $notification = Notification::where('user_id', $this->manager->id)
            ->where('type', 'Project Approval Required')
            ->where('link_id', $project->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Resubmitted for Approval', $notification->title);

        // Assert that manager can now approve the resubmitted project
        $this->actingAs($this->manager);
        Livewire::test(ProjectDetail::class, ['project' => $fresh])
            ->call('approveProject')
            ->assertHasNoErrors();

        $this->assertTrue($fresh->fresh()->isApproved());
    }
}
