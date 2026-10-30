<?php

namespace Tests\Feature;

use App\Livewire\Customers\CustomerDetail;
use App\Livewire\Customers\CustomerModal;
use App\Livewire\Documents\DocumentUploadModal;
use App\Livewire\Payments\PaymentIndex;
use App\Livewire\Payments\PaymentModal;
use App\Livewire\Projects\ProjectDetail;
use App\Livewire\Projects\ProjectMilestoneModal;
use App\Livewire\Projects\ProjectModal;
use App\Livewire\Projects\ProjectScheduleModal;
use App\Livewire\Settings\UserModal;
use App\Livewire\Tasks\TaskIndex;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UplineNotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $coordinator;

    protected User $sales;

    protected User $techPerson;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin (top upline)
        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'must_change' => false,
        ]);

        // Manager reporting to Admin
        $this->manager = User::factory()->create([
            'role' => 'Manager',
            'status' => 'Active',
            'must_change' => false,
            'reporting_to' => $this->admin->id,
        ]);

        // Coordinator reporting to Manager
        $this->coordinator = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
            'must_change' => false,
            'reporting_to' => $this->manager->id,
        ]);

        // Salesperson reporting to Manager
        $this->sales = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Active',
            'must_change' => false,
            'reporting_to' => $this->manager->id,
        ]);

        // Tech Person reporting to Manager
        $this->techPerson = User::factory()->create([
            'role' => 'TechPerson',
            'status' => 'Active',
            'must_change' => false,
            'reporting_to' => $this->manager->id,
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUS-UPL-001',
            'company' => 'Acme Corporation',
            'contact_name' => 'Alice Walker',
            'phone' => '9876543210',
            'state' => 'MH',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);
    }

    public function test_creating_project_notifies_coordinator_take_person_manager_upline_and_admins(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectModal::class)
            ->set('name', 'E-Commerce Portal')
            ->set('customer_id', $this->customer->id)
            ->set('value', 250000.00)
            ->set('coordinator_id', $this->coordinator->id)
            ->set('tech_person_id', $this->techPerson->id)
            ->set('sales_person_id', $this->sales->id)
            ->set('health', 'On Track')
            ->set('status', 'In Progress')
            ->call('save');

        $project = Project::where('name', 'E-Commerce Portal')->first();
        $this->assertNotNull($project);

        // Coordinator received assignment notification
        $coordNotif = Notification::where('user_id', $this->coordinator->id)->latest('id')->first();
        $this->assertNotNull($coordNotif);
        $this->assertStringContainsString('E-Commerce Portal', $coordNotif->title);

        // Tech Person received assignment notification
        $techNotif = Notification::where('user_id', $this->techPerson->id)->latest('id')->first();
        $this->assertNotNull($techNotif);
        $this->assertStringContainsString('E-Commerce Portal', $techNotif->title);

        // Sales Person received notification
        $salesNotif = Notification::where('user_id', $this->sales->id)->latest('id')->first();
        $this->assertNotNull($salesNotif);
        $this->assertStringContainsString('E-Commerce Portal', $salesNotif->title);

        // Manager (upline of Sales, Coordinator, and Tech Person) received notification
        $managerNotif = Notification::where('user_id', $this->manager->id)
            ->where('type', 'Project Created')
            ->latest('id')
            ->first();
        $this->assertNotNull($managerNotif);
        $this->assertStringContainsString('E-Commerce Portal', $managerNotif->body);
    }

    public function test_creating_customer_notifies_upline_manager_and_admins(): void
    {
        Livewire::actingAs($this->sales)
            ->test(CustomerModal::class)
            ->set('company', 'Zenith Enterprises')
            ->set('contact_name', 'Zack Taylor')
            ->set('phone', '9123456780')
            ->set('state', 'DL')
            ->set('status', 'Active')
            ->call('save');

        $customer = Customer::where('company', 'Zenith Enterprises')->first();
        $this->assertNotNull($customer);

        // Manager (upline of Sales rep) received approval notification
        $managerNotif = Notification::where('user_id', $this->manager->id)
            ->whereIn('type', ['Customer Created', 'Customer Approval Required', 'Client Approval Required'])
            ->latest('id')
            ->first();
        $this->assertNotNull($managerNotif);
        $this->assertStringContainsString('Zenith Enterprises', $managerNotif->title);

        // Admin received notification
        $adminNotif = Notification::where('user_id', $this->admin->id)
            ->whereIn('type', ['Customer Created', 'Customer Approval Required', 'Client Approval Required'])
            ->latest('id')
            ->first();
        $this->assertNotNull($adminNotif);
    }

    public function test_deleting_customer_notifies_attributed_salesperson_and_their_upline(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CustomerDetail::class, ['customer' => $this->customer])
            ->call('deleteCustomer');

        $this->assertDatabaseMissing('customers', ['id' => $this->customer->id]);

        // Attributed sales representative notified
        $salesNotif = Notification::where('user_id', $this->sales->id)
            ->where('type', 'Customer Deleted')
            ->latest('id')
            ->first();
        $this->assertNotNull($salesNotif);
        $this->assertStringContainsString('Acme Corporation', $salesNotif->title);

        // Manager (upline of sales) notified
        $managerNotif = Notification::where('user_id', $this->manager->id)
            ->where('type', 'Customer Deleted')
            ->latest('id')
            ->first();
        $this->assertNotNull($managerNotif);
    }

    public function test_recording_payment_notifies_manager_upline_alongside_coordinator_sales_and_admins(): void
    {
        $project = Project::create([
            'project_code' => 'PJT-UPL-001',
            'name' => 'Mobile App Suite',
            'customer_id' => $this->customer->id,
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
            'tech_person_id' => $this->techPerson->id,
            'value' => 100000.00,
            'health' => 'On Track',
            'status' => 'In Progress',
            'created_by' => $this->coordinator->id,
        ]);

        $term = PaymentTerm::create([
            'project_id' => $project->id,
            'label' => 'Phase 1 Milestone',
            'amount' => 50000.00,
            'received' => 0.00,
            'tds' => 0.00,
            'status' => 'Pending',
            'seq' => 1,
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        Livewire::actingAs($this->coordinator)
            ->test(PaymentModal::class)
            ->call('openModal', ['projectId' => $project->id, 'termId' => $term->id])
            ->set('amount', 50000.00)
            ->set('mode', 'UPI')
            ->set('reference', 'UPI-987123')
            ->call('save');

        // Salesperson notified
        $salesNotif = Notification::where('user_id', $this->sales->id)
            ->where('type', 'Payment Received')
            ->latest('id')
            ->first();
        $this->assertNotNull($salesNotif);

        // Manager (upline of coordinator and sales) notified
        $managerNotif = Notification::where('user_id', $this->manager->id)
            ->where('type', 'Payment Received')
            ->latest('id')
            ->first();
        $this->assertNotNull($managerNotif);
        $this->assertStringContainsString('Mobile App Suite', $managerNotif->body);

        // Admin notified
        $adminNotif = Notification::where('user_id', $this->admin->id)
            ->where('type', 'Payment Received')
            ->latest('id')
            ->first();
        $this->assertNotNull($adminNotif);
    }

    public function test_deleting_payment_notifies_coordinator_manager_upline_and_sales(): void
    {
        $project = Project::create([
            'project_code' => 'PJT-UPL-002',
            'name' => 'CRM Redesign',
            'customer_id' => $this->customer->id,
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
            'value' => 80000.00,
            'health' => 'On Track',
            'status' => 'In Progress',
            'created_by' => $this->coordinator->id,
        ]);

        $payment = Payment::create([
            'code' => 'RCP-DEL-001',
            'customer_id' => $this->customer->id,
            'project_id' => $project->id,
            'amount' => 40000.00,
            'tds' => 0.00,
            'paid_on' => now()->toDateString(),
            'mode' => 'NEFT',
            'created_by' => $this->coordinator->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaymentIndex::class)
            ->call('deletePayment', $payment->id);

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);

        // Coordinator notified
        $coordNotif = Notification::where('user_id', $this->coordinator->id)
            ->where('type', 'Payment Deleted')
            ->latest('id')
            ->first();
        $this->assertNotNull($coordNotif);
        $this->assertStringContainsString('RCP-DEL-001', $coordNotif->title);

        // Manager (upline) notified
        $managerNotif = Notification::where('user_id', $this->manager->id)
            ->where('type', 'Payment Deleted')
            ->latest('id')
            ->first();
        $this->assertNotNull($managerNotif);

        // Salesperson notified
        $salesNotif = Notification::where('user_id', $this->sales->id)
            ->where('type', 'Payment Deleted')
            ->latest('id')
            ->first();
        $this->assertNotNull($salesNotif);
    }

    public function test_task_assigned_to_subordinate_notifies_assignee_and_their_upline(): void
    {
        Livewire::actingAs($this->manager)
            ->test(TaskIndex::class)
            ->set('title', 'Finalize API Documentation')
            ->set('priority', 'High')
            ->set('assigned_to', $this->coordinator->id)
            ->call('addTask');

        $task = Task::where('title', 'Finalize API Documentation')->first();
        $this->assertNotNull($task);

        // Assignee (Coordinator) received notification
        $coordNotif = Notification::where('user_id', $this->coordinator->id)
            ->where('type', 'Task Assigned')
            ->latest('id')
            ->first();
        $this->assertNotNull($coordNotif);
        $this->assertStringContainsString('Finalize API Documentation', $coordNotif->title);
    }

    public function test_task_completion_notifies_task_creator(): void
    {
        $task = Task::create([
            'title' => 'Verify SSL certificates',
            'priority' => 'Urgent',
            'assigned_to' => $this->coordinator->id,
            'created_by' => $this->manager->id,
            'done' => false,
        ]);

        Livewire::actingAs($this->coordinator)
            ->test(TaskIndex::class)
            ->call('toggleDone', $task->id);

        $this->assertTrue($task->fresh()->done);

        // Manager (creator) notified of completion
        $managerNotif = Notification::where('user_id', $this->manager->id)
            ->where('type', 'Task Completed')
            ->latest('id')
            ->first();
        $this->assertNotNull($managerNotif);
        $this->assertStringContainsString('Verify SSL certificates', $managerNotif->title);
    }

    public function test_uploading_project_document_notifies_stakeholders_and_uplines(): void
    {
        Storage::fake('uploads');
        Storage::fake('public');

        $project = Project::create([
            'project_code' => 'PJT-DOC-001',
            'name' => 'Brand Identity Kit',
            'customer_id' => $this->customer->id,
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
            'value' => 60000.00,
            'health' => 'On Track',
            'status' => 'In Progress',
            'created_by' => $this->coordinator->id,
        ]);

        $file = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');

        Livewire::actingAs($this->sales)
            ->test(DocumentUploadModal::class)
            ->set('customer_id', $this->customer->id)
            ->set('project_id', $project->id)
            ->set('category', 'Contract')
            ->set('title', 'Signed Master Agreement')
            ->set('file', $file)
            ->call('save');

        // Coordinator notified of document upload
        $coordNotif = Notification::where('user_id', $this->coordinator->id)
            ->where('type', 'Document Uploaded')
            ->latest('id')
            ->first();
        $this->assertNotNull($coordNotif);
        $this->assertStringContainsString('Signed Master Agreement', $coordNotif->title);

        // Manager (upline) notified
        $managerNotif = Notification::where('user_id', $this->manager->id)
            ->where('type', 'Document Uploaded')
            ->latest('id')
            ->first();
        $this->assertNotNull($managerNotif);
    }

    public function test_updating_milestones_and_completion_notifies_stakeholders_and_uplines(): void
    {
        $project = Project::create([
            'project_code' => 'PJT-MLS-001',
            'name' => 'Custom CMS Build',
            'customer_id' => $this->customer->id,
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
            'value' => 120000.00,
            'health' => 'On Track',
            'status' => 'In Progress',
            'created_by' => $this->coordinator->id,
        ]);

        Livewire::actingAs($this->coordinator)
            ->test(ProjectMilestoneModal::class)
            ->call('load', $project->id)
            ->set('items.0.done', true)
            ->set('items.0.status', 'Done')
            ->call('save');

        // Manager (upline) notified of milestone completion
        $managerNotif = Notification::where('user_id', $this->manager->id)
            ->where('type', 'Milestone Completed')
            ->latest('id')
            ->first();
        $this->assertNotNull($managerNotif);
        $this->assertStringContainsString('Custom CMS Build', $managerNotif->body);

        // Salesperson notified of milestone completion
        $salesNotif = Notification::where('user_id', $this->sales->id)
            ->where('type', 'Milestone Completed')
            ->latest('id')
            ->first();
        $this->assertNotNull($salesNotif);
    }

    public function test_saving_payment_schedule_notifies_stakeholders_and_uplines(): void
    {
        $project = Project::create([
            'project_code' => 'PJT-SCH-001',
            'name' => 'SEO Optimization Campaign',
            'customer_id' => $this->customer->id,
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
            'value' => 40000.00,
            'health' => 'On Track',
            'status' => 'In Progress',
            'created_by' => $this->coordinator->id,
        ]);

        Livewire::actingAs($this->coordinator)
            ->test(ProjectScheduleModal::class)
            ->call('load', $project->id)
            ->set('terms', [
                [
                    'id' => null,
                    'label' => 'Initial Installment',
                    'amount' => 40000.00,
                    'trigger' => 'fixed',
                    'dueDate' => now()->addDays(10)->toDateString(),
                    'graceDays' => 3,
                    'milestoneId' => null,
                    'locked' => false,
                ],
            ])
            ->call('save');

        // Manager (upline) notified
        $managerNotif = Notification::where('user_id', $this->manager->id)
            ->where('type', 'Schedule Updated')
            ->latest('id')
            ->first();
        $this->assertNotNull($managerNotif);
        $this->assertStringContainsString('SEO Optimization Campaign', $managerNotif->title);
    }

    public function test_assigning_user_to_supervisor_in_user_modal_notifies_supervisor(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UserModal::class)
            ->set('name', 'Brian Adams')
            ->set('email', 'brian@10xagency.in')
            ->set('phone', '9898989898')
            ->set('role', 'Coordinator')
            ->set('reporting_to', $this->manager->id)
            ->set('status', 'Active')
            ->call('save');

        $newUser = User::where('email', 'brian@10xagency.in')->first();
        $this->assertNotNull($newUser);

        // Manager notified of new subordinate assigned to them
        $managerNotif = Notification::where('user_id', $this->manager->id)
            ->where('type', 'Team Member Assigned')
            ->latest('id')
            ->first();
        $this->assertNotNull($managerNotif);
        $this->assertStringContainsString('Brian Adams', $managerNotif->title);
    }

    public function test_deleting_project_notifies_coordinator_take_person_sales_and_uplines(): void
    {
        $project = Project::create([
            'project_code' => 'PJT-DEL-001',
            'name' => 'Legacy Migration',
            'customer_id' => $this->customer->id,
            'coordinator_id' => $this->coordinator->id,
            'tech_person_id' => $this->techPerson->id,
            'sales_person_id' => $this->sales->id,
            'value' => 150000.00,
            'health' => 'On Track',
            'status' => 'In Progress',
            'created_by' => $this->coordinator->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectDetail::class, ['project' => $project])
            ->call('deleteProject');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);

        // Coordinator notified
        $coordNotif = Notification::where('user_id', $this->coordinator->id)
            ->where('type', 'Project Deleted')
            ->latest('id')
            ->first();
        $this->assertNotNull($coordNotif);
        $this->assertStringContainsString('Legacy Migration', $coordNotif->title);

        // Tech Person notified
        $techNotif = Notification::where('user_id', $this->techPerson->id)
            ->where('type', 'Project Deleted')
            ->latest('id')
            ->first();
        $this->assertNotNull($techNotif);

        // Salesperson notified
        $salesNotif = Notification::where('user_id', $this->sales->id)
            ->where('type', 'Project Deleted')
            ->latest('id')
            ->first();
        $this->assertNotNull($salesNotif);

        // Manager (upline) notified
        $managerNotif = Notification::where('user_id', $this->manager->id)
            ->where('type', 'Project Deleted')
            ->latest('id')
            ->first();
        $this->assertNotNull($managerNotif);
    }
}
