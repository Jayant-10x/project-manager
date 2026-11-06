<?php

namespace Tests\Feature;

use App\Livewire\Customers\CustomerDetail;
use App\Livewire\Customers\CustomerIndex;
use App\Livewire\Customers\CustomerModal;
use App\Livewire\Payments\PaymentModal;
use App\Livewire\Projects\ProjectDetail;
use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Projects\ProjectMilestoneModal;
use App\Livewire\Projects\ProjectModal;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Milestone;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientsAndProjectsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $coordinator;

    protected User $coordinator2;

    protected User $sales;

    protected User $sales2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'name' => 'Admin User',
            'email' => 'admin@test.com',
        ]);

        $this->manager = User::factory()->create([
            'role' => 'Manager',
            'status' => 'Active',
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'reporting_to' => $this->admin->id,
        ]);

        $this->coordinator = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
            'name' => 'Coordinator 1',
            'email' => 'coordinator1@test.com',
            'reporting_to' => $this->manager->id,
        ]);

        $this->coordinator2 = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
            'name' => 'Coordinator 2',
            'email' => 'coordinator2@test.com',
            'reporting_to' => $this->admin->id,
        ]);

        $this->sales = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Active',
            'name' => 'Sales 1',
            'email' => 'sales1@test.com',
            'reporting_to' => $this->coordinator->id,
        ]);

        $this->sales2 = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Active',
            'name' => 'Sales 2',
            'email' => 'sales2@test.com',
            'reporting_to' => $this->coordinator->id,
        ]);
    }

    public function test_all_roles_can_access_customers_and_projects_indices(): void
    {
        foreach ([$this->admin, $this->manager, $this->coordinator, $this->sales] as $user) {
            $this->actingAs($user)->get('/customers')->assertStatus(200);
            $this->actingAs($user)->get('/projects')->assertStatus(200);
        }
    }

    public function test_sales_person_creating_customer_auto_assigns_sales_person_id_and_hides_input(): void
    {
        Livewire::actingAs($this->sales)
            ->test(CustomerModal::class)
            ->call('loadCustomer')
            ->assertDontSee('Onboarded by (salesperson)')
            ->set('contact_name', 'Acme Corp Lead')
            ->set('phone', '9876543210')
            ->set('state', 'MH')
            ->call('save')
            ->assertHasNoErrors();

        $customer = Customer::where('contact_name', 'Acme Corp Lead')->first();
        $this->assertNotNull($customer);
        $this->assertEquals($this->sales->id, $customer->sales_person_id);
    }

    public function test_coordinator_creating_project_auto_assigns_coordinator_id_and_hides_input(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-001',
            'contact_name' => 'Test Client',
            'phone' => '9876543211',
            'state' => 'MH',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'status' => 'Active',
        ]);

        Livewire::actingAs($this->coordinator)
            ->test(ProjectModal::class)
            ->call('loadProject')
            ->assertDontSee('— Assign Staff Member —')
            ->set('customer_id', $customer->id)
            ->set('name', 'E-Commerce Website')
            ->set('project_type', 'Website')
            ->set('value', 50000)
            ->set('cost', 20000)
            ->set('sales_person_id', $this->sales->id)
            ->set('start_date', now()->toDateString())
            ->set('status', 'In Progress')
            ->set('health', 'On Track')
            ->call('save')
            ->assertHasNoErrors();

        $project = Project::where('name', 'E-Commerce Website')->first();
        $this->assertNotNull($project);
        $this->assertEquals($this->coordinator->id, $project->coordinator_id);
        $this->assertEquals($this->sales->id, $project->sales_person_id);
    }

    public function test_sales_person_creating_project_auto_assigns_sales_person_id_and_hides_input(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-002',
            'contact_name' => 'Another Client',
            'phone' => '9876543212',
            'state' => 'MH',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'status' => 'Active',
        ]);

        Livewire::actingAs($this->sales)
            ->test(ProjectModal::class)
            ->call('loadProject')
            ->assertDontSee('— Select Salesperson —')
            ->assertDontSee('— Assign Staff Member —')
            ->assertSee($this->coordinator->name)
            ->set('customer_id', $customer->id)
            ->set('name', 'Mobile Application')
            ->set('project_type', 'Mobile App')
            ->set('value', 80000)
            ->set('cost', 30000)
            ->set('coordinator_id', $this->coordinator2->id) // Attempting to tamper/change coordinator
            ->set('start_date', now()->toDateString())
            ->set('status', 'In Progress')
            ->set('health', 'On Track')
            ->call('save')
            ->assertHasNoErrors();

        $project = Project::where('name', 'Mobile Application')->first();
        $this->assertNotNull($project);
        $this->assertEquals($this->sales->id, $project->sales_person_id);
        $this->assertEquals($this->coordinator->id, $project->coordinator_id, 'Coordinator must be locked to reporting_to manager');
    }

    public function test_role_based_scoping_and_visibility_for_projects(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-003',
            'contact_name' => 'Shared Client',
            'phone' => '9876543213',
            'state' => 'MH',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'status' => 'Active',
        ]);

        $project1 = Project::create([
            'project_code' => 'PJT-001',
            'customer_id' => $customer->id,
            'name' => 'Project for Sales 1 & Coord 1',
            'value' => 10000,
            'cost' => 5000,
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'start_date' => now()->toDateString(),
            'created_by' => $this->coordinator->id,
        ]);

        $project2 = Project::create([
            'project_code' => 'PJT-002',
            'customer_id' => $customer->id,
            'name' => 'Project for Sales 2 & Coord 2',
            'value' => 20000,
            'cost' => 8000,
            'coordinator_id' => $this->coordinator2->id,
            'sales_person_id' => $this->sales2->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'start_date' => now()->toDateString(),
            'created_by' => $this->coordinator2->id,
        ]);

        // Sales 1 can view project 1, but forbidden on project 2
        $this->actingAs($this->sales)->get("/project/{$project1->id}")->assertStatus(200);
        $this->actingAs($this->sales)->get("/project/{$project2->id}")->assertStatus(403);

        // Coordinator 1 can view project 1, but forbidden on project 2
        $this->actingAs($this->coordinator)->get("/project/{$project1->id}")->assertStatus(200);
        $this->actingAs($this->coordinator)->get("/project/{$project2->id}")->assertStatus(403);

        // Admin can view both
        $this->actingAs($this->admin)->get("/project/{$project1->id}")->assertStatus(200);
        $this->actingAs($this->admin)->get("/project/{$project2->id}")->assertStatus(200);
    }

    public function test_customer_index_hides_import_export_and_filters_by_state(): void
    {
        Customer::create([
            'customer_code' => 'CUS-MH',
            'contact_name' => 'Maharashtra Client',
            'phone' => '9876543221',
            'state' => 'MH',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'status' => 'Active',
        ]);

        Customer::create([
            'customer_code' => 'CUS-DL',
            'contact_name' => 'Delhi Client',
            'phone' => '9876543222',
            'state' => 'DL',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'status' => 'Active',
        ]);

        Livewire::actingAs($this->admin)
            ->test(CustomerIndex::class)
            ->assertDontSee('Export CSV')
            ->assertDontSee('Bulk Import')
            ->assertSee('Maharashtra Client')
            ->assertSee('Delhi Client')
            ->set('state', 'MH')
            ->assertSee('Maharashtra Client')
            ->assertDontSee('Delhi Client');
    }

    public function test_customer_detail_hides_followup_and_shows_project_name_in_payments(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-PAY',
            'contact_name' => 'Payment Test Client',
            'company' => 'Acme Technologies',
            'phone' => '9876543223',
            'state' => 'MH',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'status' => 'Active',
        ]);

        $project = Project::create([
            'project_code' => 'PJT-PAY-1',
            'customer_id' => $customer->id,
            'name' => 'Cloud ERP System',
            'value' => 50000,
            'cost' => 20000,
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'start_date' => now()->toDateString(),
            'created_by' => $this->coordinator->id,
        ]);

        PaymentTerm::create([
            'project_id' => $project->id,
            'seq' => 1,
            'label' => 'Initial Advance',
            'amount' => 20000,
            'received' => 20000,
            'due_date' => now()->toDateString(),
            'trigger_type' => 'fixed',
            'status' => 'Paid',
        ]);

        Payment::create([
            'code' => 'REC-001',
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'amount' => 20000,
            'paid_on' => now(),
            'mode' => 'NEFT',
            'reference' => 'TXN12345',
            'created_by' => $this->coordinator->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CustomerDetail::class, ['customer' => $customer])
            ->assertDontSee('Log Follow-up')
            ->assertDontSee('Follow-ups')
            ->set('activeTab', 'payments')
            ->assertSee('Cloud ERP System');
    }

    public function test_customer_detail_documents_search_category_and_project_filtering(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-DOCS',
            'contact_name' => 'Document Test Client',
            'company' => 'Beta Industries',
            'phone' => '9876543224',
            'state' => 'KA',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
            'status' => 'Active',
        ]);

        $project = Project::create([
            'project_code' => 'PJT-DOC-1',
            'customer_id' => $customer->id,
            'name' => 'Internal Dashboard Project',
            'value' => 40000,
            'cost' => 15000,
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'start_date' => now()->toDateString(),
            'created_by' => $this->coordinator->id,
        ]);

        Document::create([
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'title' => 'Master Services Agreement',
            'file_name' => 'msa-signed.pdf',
            'category' => 'Contract',
            'mime' => 'application/pdf',
            'size_kb' => 240,
            'stored_as' => 'documents/msa-signed.pdf',
            'uploaded_by' => $this->admin->id,
        ]);

        Document::create([
            'customer_id' => $customer->id,
            'project_id' => null,
            'title' => 'GST Registration Certificate',
            'file_name' => 'gst-cert.pdf',
            'category' => 'KYC/GST',
            'mime' => 'application/pdf',
            'size_kb' => 120,
            'stored_as' => 'documents/gst-cert.pdf',
            'uploaded_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CustomerDetail::class, ['customer' => $customer])
            ->set('activeTab', 'documents')
            ->assertSee('Master Services Agreement')
            ->assertSee('GST Registration Certificate')
            // Test search filter
            ->set('docSearch', 'Master')
            ->assertSee('Master Services Agreement')
            ->assertDontSee('GST Registration Certificate')
            // Clear search, filter by category
            ->set('docSearch', '')
            ->set('docCategory', 'KYC/GST')
            ->assertDontSee('Master Services Agreement')
            ->assertSee('GST Registration Certificate')
            // Filter by project
            ->set('docCategory', '')
            ->set('docProject', (string) $project->id)
            ->assertSee('Master Services Agreement')
            ->assertDontSee('GST Registration Certificate')
            // Filter by general (no project)
            ->set('docProject', 'general')
            ->assertDontSee('Master Services Agreement')
            ->assertSee('GST Registration Certificate')
            // Clear filters
            ->call('clearDocFilters')
            ->assertSee('Master Services Agreement')
            ->assertSee('GST Registration Certificate');
    }

    public function test_inactive_users_are_excluded_from_selection_dropdowns(): void
    {
        $inactiveSales = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Inactive',
            'name' => 'Inactive Salesperson',
            'email' => 'inactivesales@test.com',
        ]);

        $inactiveCoord = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Inactive',
            'name' => 'Inactive Coordinator',
            'email' => 'inactivecoord@test.com',
        ]);

        Livewire::actingAs($this->admin)
            ->test(CustomerIndex::class)
            ->assertSee($this->sales->name)
            ->assertDontSee($inactiveSales->name);

        Livewire::actingAs($this->admin)
            ->test(ProjectIndex::class)
            ->assertSee($this->sales->name)
            ->assertDontSee($inactiveSales->name)
            ->assertDontSee($inactiveCoord->name);
    }

    public function test_option_a_payment_modal_auto_populates_installment_term_and_balance(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-OPA-1',
            'contact_name' => 'Option A Customer',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-OPA-1',
            'customer_id' => $customer->id,
            'name' => 'Option A Project',
            'status' => 'In Progress',
            'value' => 50000,
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
        ]);

        $term = PaymentTerm::create([
            'project_id' => $project->id,
            'seq' => 1,
            'label' => 'Advance Payment',
            'trigger_type' => 'fixed',
            'amount' => 20000,
            'received' => 5000,
            'tds' => 0,
            'status' => 'Partial',
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaymentModal::class)
            ->dispatch('open-payment-modal', projectId: $project->id, termId: $term->id)
            ->assertSet('project_id', $project->id)
            ->assertSet('term_id', $term->id)
            ->assertSet('amount', 15000.0)
            ->assertDispatched('open-modal', 'payment-modal');
    }

    public function test_option_b_marking_milestone_done_prompts_payment_for_pending_installment(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-OPB-1',
            'contact_name' => 'Option B Customer',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-OPB-1',
            'customer_id' => $customer->id,
            'name' => 'Option B Project',
            'status' => 'In Progress',
            'value' => 50000,
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
        ]);

        $milestone = Milestone::create([
            'project_id' => $project->id,
            'name' => 'Design Sign-off',
            'status' => 'Pending',
            'done' => false,
            'seq' => 1,
        ]);

        $term = PaymentTerm::create([
            'project_id' => $project->id,
            'seq' => 1,
            'label' => 'Design Completion Milestone',
            'trigger_type' => 'milestone',
            'amount' => 30000,
            'received' => 0,
            'tds' => 0,
            'status' => 'Pending',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectDetail::class, ['project' => $project])
            ->call('toggleMilestone', $milestone->id)
            ->assertDispatched('prompt-milestone-payment', function ($name, $params) use ($project, $term) {
                $data = is_array($params) && isset($params[0]) ? $params[0] : $params;

                return ($data['milestoneName'] ?? null) === 'Design Sign-off'
                    && ($data['termLabel'] ?? null) === 'Design Completion Milestone'
                    && ($data['projectId'] ?? null) === $project->id
                    && ($data['termId'] ?? null) === $term->id
                    && (float) ($data['balance'] ?? 0) === 30000.0;
            });

        $this->assertTrue($milestone->fresh()->done);
    }

    public function test_option_c_opening_payment_modal_with_customer_id_filters_or_selects_customer_project(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-OPC-1',
            'contact_name' => 'Option C Customer',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-OPC-1',
            'customer_id' => $customer->id,
            'name' => 'Option C Project',
            'status' => 'In Progress',
            'value' => 60000,
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaymentModal::class)
            ->dispatch('open-payment-modal', customerId: $customer->id)
            ->assertSet('customer_id', $customer->id)
            ->assertSet('project_id', $project->id)
            ->assertDispatched('open-modal', 'payment-modal');
    }

    public function test_sales_role_cannot_record_payments_and_milestone_toggle_does_not_prompt_sales(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-OPS-1',
            'contact_name' => 'Option Sales Customer',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-OPS-1',
            'customer_id' => $customer->id,
            'name' => 'Option Sales Project',
            'status' => 'In Progress',
            'sales_person_id' => $this->sales->id,
            'coordinator_id' => $this->coordinator->id,
        ]);

        $milestone = Milestone::create([
            'project_id' => $project->id,
            'name' => 'Initial Review',
            'status' => 'Pending',
            'done' => false,
            'seq' => 1,
        ]);

        PaymentTerm::create([
            'project_id' => $project->id,
            'seq' => 1,
            'label' => 'Initial Milestone',
            'trigger_type' => 'milestone',
            'amount' => 10000,
            'status' => 'Pending',
        ]);

        // Sales user toggling milestone does NOT trigger payment prompt
        Livewire::actingAs($this->sales)
            ->test(ProjectDetail::class, ['project' => $project])
            ->call('toggleMilestone', $milestone->id)
            ->assertNotDispatched('prompt-milestone-payment');

        // Sales user calling save on PaymentModal gets 403
        Livewire::actingAs($this->sales)
            ->test(PaymentModal::class)
            ->set('project_id', $project->id)
            ->set('amount', 5000)
            ->set('paid_on', now()->toDateString())
            ->set('mode', 'NEFT')
            ->call('save')
            ->assertStatus(403);
    }

    public function test_settling_payment_updates_installment_status_and_received_amount(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-OPF-1',
            'contact_name' => 'Option Final Customer',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-OPF-1',
            'customer_id' => $customer->id,
            'name' => 'Option Final Project',
            'status' => 'In Progress',
            'value' => 25000,
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
        ]);

        $term = PaymentTerm::create([
            'project_id' => $project->id,
            'seq' => 1,
            'label' => 'Final Milestone',
            'trigger_type' => 'milestone',
            'amount' => 25000,
            'received' => 0,
            'tds' => 0,
            'status' => 'Pending',
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaymentModal::class)
            ->dispatch('open-payment-modal', projectId: $project->id, termId: $term->id)
            ->call('save')
            ->assertDispatched('payment-saved')
            ->assertDispatched('close-modal', 'payment-modal');

        $freshTerm = $term->fresh();
        $this->assertEquals(25000, $freshTerm->received);
        $this->assertEquals('Paid', $freshTerm->status);
    }

    public function test_delay_reason_field_is_only_shown_when_health_or_status_is_delayed(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-DLY-1',
            'contact_name' => 'Delay Customer',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        // 1. Initial state (health = On Track): Delay reason field is hidden
        $component = Livewire::actingAs($this->admin)
            ->test(ProjectModal::class)
            ->call('loadProject')
            ->assertSet('health', 'On Track')
            ->assertDontSee('Delay reason');

        // 2. Set health to Delayed: Delay reason field appears
        $component->set('health', 'Delayed')
            ->assertSee('Delay reason');

        // 3. Set health back to On Track: Delay reason field is hidden and cleared
        $component->set('delay_reason', 'Awaiting client approval')
            ->set('health', 'On Track')
            ->assertDontSee('Delay reason')
            ->assertSet('delay_reason', '');

        // 4. Saving when health is not Delayed stores delay_reason as null
        $component->set('name', 'On Track Project')
            ->set('customer_id', $customer->id)
            ->set('value', 15000)
            ->set('coordinator_id', $this->coordinator->id)
            ->set('sales_person_id', $this->sales->id)
            ->set('start_date', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $savedProject = Project::where('name', 'On Track Project')->first();
        $this->assertNotNull($savedProject);
        $this->assertNull($savedProject->delay_reason);

        // 5. Saving when health is Delayed stores delay_reason
        Livewire::actingAs($this->admin)
            ->test(ProjectModal::class)
            ->call('loadProject', $savedProject->id)
            ->assertDontSee('Delay reason')
            ->set('health', 'Delayed')
            ->assertSee('Delay reason')
            ->set('delay_reason', 'Client delayed milestone approvals')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('Client delayed milestone approvals', $savedProject->fresh()->delay_reason);
    }

    public function test_milestone_button_changes_text_dynamically_based_on_milestone_presence(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-MS-TXT-1',
            'contact_name' => 'Milestone Text Customer',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-MS-TXT-1',
            'customer_id' => $customer->id,
            'name' => 'Milestone Button Project',
            'status' => 'In Progress',
            'value' => 30000,
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
        ]);

        // 1. No milestones exist -> Button should say "Add Milestone"
        Livewire::actingAs($this->admin)
            ->test(ProjectDetail::class, ['project' => $project])
            ->assertSeeHtml('<span>Add Milestone</span>')
            ->assertDontSeeHtml('<span>Edit Milestone</span>');

        // 2. ProjectMilestoneModal should reflect "Add Milestones"
        Livewire::actingAs($this->admin)
            ->test(ProjectMilestoneModal::class)
            ->call('load', $project->id)
            ->assertSee('Add Milestones');

        // 3. Create a milestone for the project
        Milestone::create([
            'project_id' => $project->id,
            'name' => 'Wireframes approved',
            'sort_order' => 1,
            'status' => 'In Progress',
            'done' => false,
        ]);

        // 4. Milestones now exist -> Button should say "Edit Milestone"
        Livewire::actingAs($this->admin)
            ->test(ProjectDetail::class, ['project' => $project->fresh()])
            ->assertSeeHtml('<span>Edit Milestone</span>')
            ->assertDontSeeHtml('<span>Add Milestone</span>');

        // 5. ProjectMilestoneModal should reflect "Edit Milestones"
        Livewire::actingAs($this->admin)
            ->test(ProjectMilestoneModal::class)
            ->call('load', $project->id)
            ->assertSee('Edit Milestones');
    }

    public function test_overdue_milestones_and_projects_display_integer_days_late_without_decimals(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-INT-1',
            'contact_name' => 'Integer Days Customer',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-INT-1',
            'customer_id' => $customer->id,
            'name' => 'Integer Days Project',
            'status' => 'In Progress',
            'health' => 'Delayed',
            'value' => 45000,
            'delivery_date' => now()->subDays(2)->toDateString(),
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
        ]);

        Milestone::create([
            'project_id' => $project->id,
            'name' => 'Kickoff & requirement gathering',
            'due_date' => now()->subDay()->toDateString(),
            'sort_order' => 1,
            'status' => 'Pending',
            'done' => false,
        ]);

        $html = Livewire::actingAs($this->admin)
            ->test(ProjectDetail::class, ['project' => $project])
            ->assertSee('1 days late')
            ->assertSee('2 days late')
            ->html();

        // Ensure no floating-point day values appear anywhere in the output
        $this->assertDoesNotMatchRegularExpression('/\d+\.\d+\s*days late/i', $html);
    }

    public function test_customer_index_payment_filter_due_overdue_and_clear(): void
    {
        // 1. Customer with Overdue term
        $overdueCustomer = Customer::create([
            'customer_code' => 'CUS-OVER',
            'company' => 'Overdue Solutions',
            'contact_name' => 'Overdue Client',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);
        $overdueProject = Project::create([
            'project_code' => 'PJT-OVER-1',
            'customer_id' => $overdueCustomer->id,
            'name' => 'Overdue App',
            'value' => 50000,
            'cost' => 15000,
            'status' => 'In Progress',
            'health' => 'Delayed',
            'sales_person_id' => $this->sales->id,
        ]);
        PaymentTerm::create([
            'project_id' => $overdueProject->id,
            'seq' => 1,
            'label' => 'Past Term',
            'amount' => 50000,
            'received' => 0,
            'tds' => 0,
            'status' => 'Overdue',
            'due_date' => now()->subDays(10)->toDateString(),
        ]);

        // 2. Customer with Clear / Fully Paid term
        $paidCustomer = Customer::create([
            'customer_code' => 'CUS-PAID',
            'company' => 'Paid Corp',
            'contact_name' => 'Paid Client',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);
        $paidProject = Project::create([
            'project_code' => 'PJT-PAID-1',
            'customer_id' => $paidCustomer->id,
            'name' => 'Paid App',
            'value' => 30000,
            'cost' => 10000,
            'status' => 'Delivered',
            'health' => 'On Track',
            'sales_person_id' => $this->sales->id,
        ]);
        $paidTerm = PaymentTerm::create([
            'project_id' => $paidProject->id,
            'seq' => 1,
            'label' => 'Full Settlement',
            'amount' => 30000,
            'received' => 30000,
            'tds' => 0,
            'status' => 'Paid',
            'due_date' => now()->subDays(5)->toDateString(),
        ]);
        Payment::create([
            'code' => 'RCP-TEST-1',
            'customer_id' => $paidCustomer->id,
            'project_id' => $paidProject->id,
            'term_id' => $paidTerm->id,
            'amount' => 30000,
            'tds' => 0,
            'paid_on' => now()->toDateString(),
            'mode' => 'NEFT',
            'created_by' => $this->admin->id,
        ]);

        // Filter: 'over'
        Livewire::actingAs($this->admin)
            ->test(CustomerIndex::class)
            ->set('pay', 'over')
            ->assertSee('Overdue Solutions')
            ->assertDontSee('Paid Corp');

        // Filter: 'clear'
        Livewire::actingAs($this->admin)
            ->test(CustomerIndex::class)
            ->set('pay', 'clear')
            ->assertSee('Paid Corp')
            ->assertDontSee('Overdue Solutions');

        // Filter: 'due'
        Livewire::actingAs($this->admin)
            ->test(CustomerIndex::class)
            ->set('pay', 'due')
            ->assertSee('Overdue Solutions')
            ->assertDontSee('Paid Corp');
    }

    public function test_customer_index_export_csv_streams_with_lazy_chunking(): void
    {
        Customer::create([
            'customer_code' => 'CUS-CSV-1',
            'company' => 'Streaming Client Corp',
            'contact_name' => 'Streaming Contact',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CustomerIndex::class)
            ->set('search', 'Streaming Client Corp')
            ->call('exportCsv')
            ->assertFileDownloaded('customers-'.now()->toDateString().'.csv');
    }
}
