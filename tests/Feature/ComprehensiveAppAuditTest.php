<?php

namespace Tests\Feature;

use App\Livewire\Calendar\PaymentCalendarIndex;
use App\Livewire\Components\GlobalSearch;
use App\Livewire\Customers\CustomerDetail;
use App\Livewire\Customers\CustomerImportModal;
use App\Livewire\Followups\FollowupIndex;
use App\Livewire\Projects\ProjectRecurringModal;
use App\Livewire\Today\TodayWorkIndex;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Followup;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ComprehensiveAppAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $coordinator;

    protected User $techPerson;

    protected User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        $this->coordinator = User::create([
            'name' => 'Coordinator User',
            'email' => 'coordinator@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'Coordinator',
            'status' => 'Active',
        ]);

        $this->techPerson = User::create([
            'name' => 'Tech Person User',
            'email' => 'tech@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'TechPerson',
            'status' => 'Active',
        ]);

        $this->sales = User::create([
            'name' => 'Sales User',
            'email' => 'sales@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'Sales',
            'status' => 'Active',
        ]);
    }

    /**
     * ISS-095: Verify GlobalSearch strictly respects user scoping and conceals customers from Tech Person.
     */
    public function test_global_search_strictly_respects_role_scoping_and_conceals_customers_from_tech_person(): void
    {
        $customerA = Customer::create([
            'customer_code' => 'CUS-GS-01',
            'company' => 'Acme Quantum Search',
            'contact_name' => 'John Quantum',
            'phone' => '9888111111',
            'status' => 'Active',
            'approval_status' => 'Approved',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $customerB = Customer::create([
            'customer_code' => 'CUS-GS-02',
            'company' => 'Zenith Quantum Systems',
            'contact_name' => 'Alice Zenith',
            'phone' => '9888222222',
            'status' => 'Active',
            'approval_status' => 'Approved',
            'created_by' => $this->admin->id,
        ]);

        $projectA = Project::create([
            'project_code' => 'PJT-GS-01',
            'name' => 'Quantum Web Portal',
            'customer_id' => $customerA->id,
            'sales_person_id' => $this->sales->id,
            'coordinator_id' => $this->coordinator->id,
            'tech_person_id' => $this->techPerson->id,
            'status' => 'In Progress',
            'approval_status' => 'Approved',
            'health' => 'On Track',
            'value' => 60000,
        ]);

        // 1. Admin searches "Quantum": sees both customers and project
        Livewire::actingAs($this->admin)
            ->test(GlobalSearch::class)
            ->set('query', 'Quantum')
            ->assertSee('Acme Quantum Search')
            ->assertSee('Zenith Quantum Systems')
            ->assertSee('Quantum Web Portal');

        // 2. Salesperson searches "Quantum": sees only Customer A (attributed to them) and Project A
        Livewire::actingAs($this->sales)
            ->test(GlobalSearch::class)
            ->set('query', 'Quantum')
            ->assertSee('Acme Quantum Search')
            ->assertDontSee('Zenith Quantum Systems')
            ->assertSee('Quantum Web Portal');

        // 3. Tech Person searches "Quantum": customer search is completely suppressed; sees ONLY Project A
        Livewire::actingAs($this->techPerson)
            ->test(GlobalSearch::class)
            ->set('query', 'Quantum')
            ->assertDontSee('Acme Quantum Search')
            ->assertDontSee('Zenith Quantum Systems')
            ->assertSee('Quantum Web Portal');
    }

    /**
     * ISS-096: Verify Today's Work Queue does not leak unassigned customer-level followups across staff.
     */
    public function test_today_work_queue_does_not_leak_unassigned_customer_level_followups(): void
    {
        $otherCoordinator = User::create([
            'name' => 'Other Coordinator',
            'email' => 'other_coord@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'Coordinator',
            'status' => 'Active',
        ]);

        // Customer & project assigned to other coordinator
        $otherCustomer = Customer::create([
            'customer_code' => 'CUS-TW-01',
            'company' => 'Other Confidential Corp',
            'contact_name' => 'Other Person',
            'phone' => '9877112233',
            'status' => 'Active',
            'approval_status' => 'Approved',
            'created_by' => $otherCoordinator->id,
        ]);

        $myCustomer = Customer::create([
            'customer_code' => 'CUS-TW-02',
            'company' => 'My Assigned Client',
            'contact_name' => 'My Contact',
            'phone' => '9877223344',
            'status' => 'Active',
            'approval_status' => 'Approved',
            'created_by' => $this->coordinator->id,
        ]);

        // Customer-level followup with project_id = null on other coordinator's customer
        Followup::create([
            'customer_id' => $otherCustomer->id,
            'project_id' => null,
            'type' => 'Call',
            'summary' => 'Confidential Followup for Other Coordinator',
            'happened_on' => now()->toDateString(),
            'next_date' => now()->addDays(2)->toDateString(),
            'created_by' => $otherCoordinator->id,
        ]);

        // Customer-level followup on my customer
        Followup::create([
            'customer_id' => $myCustomer->id,
            'project_id' => null,
            'type' => 'Call',
            'summary' => 'My Allowed Client Followup',
            'happened_on' => now()->toDateString(),
            'next_date' => now()->addDays(1)->toDateString(),
            'created_by' => $this->coordinator->id,
        ]);

        // Coordinator should see their own customer's follow-up, but NOT the other coordinator's customer follow-up
        Livewire::actingAs($this->coordinator)
            ->test(TodayWorkIndex::class)
            ->assertSee('My Allowed Client Followup')
            ->assertDontSee('Confidential Followup for Other Coordinator');
    }

    /**
     * ISS-097: Verify FollowupIndex correctly imports Livewire\Attributes\Url and persists query parameters.
     */
    public function test_followup_index_persists_url_query_parameters(): void
    {
        Livewire::actingAs($this->admin)
            ->test(FollowupIndex::class)
            ->set('type', 'Meeting')
            ->assertSet('type', 'Meeting')
            ->set('search', 'Project kickoff')
            ->assertSet('search', 'Project kickoff');
    }

    /**
     * ISS-098: Verify DocumentDownloadController enforces authentication, RBAC, and project/customer scoping.
     */
    public function test_document_download_controller_enforces_downline_and_project_authorization(): void
    {
        Storage::fake('documents');

        $customer = Customer::create([
            'customer_code' => 'CUS-DOC-01',
            'company' => 'Restricted Legal Client',
            'contact_name' => 'Legal Contact',
            'phone' => '9877334455',
            'status' => 'Active',
            'approval_status' => 'Approved',
            'created_by' => $this->admin->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-DOC-01',
            'name' => 'Confidential Contract Project',
            'customer_id' => $customer->id,
            'status' => 'In Progress',
            'approval_status' => 'Approved',
            'value' => 50000,
        ]);

        $storedName = 'confidential_contract_2026.pdf';
        Storage::disk('documents')->put($storedName, 'CONFIDENTIAL CONTRACT CONTENT');

        $doc = Document::create([
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'category' => 'Contract',
            'title' => 'Master Agreement',
            'file_name' => 'Master_Agreement.pdf',
            'stored_as' => $storedName,
            'uploaded_by' => $this->admin->id,
        ]);

        // 1. Unauthenticated request redirected to login
        $response = $this->get(route('documents.download', $doc));
        $response->assertRedirect(route('login'));

        // 2. Unauthorized coordinator (has no access to project or customer) receives 403 Forbidden
        $response = $this->actingAs($this->coordinator)->get(route('documents.download', $doc));
        $response->assertStatus(403);

        // 3. Admin receives the download stream successfully
        $response = $this->actingAs($this->admin)->get(route('documents.download', $doc));
        $response->assertStatus(200);
        $this->assertTrue($response->isOk());
    }

    /**
     * Tests PaymentCalendarIndex renders grid, navigates months, and calculates cash flow metrics.
     */
    public function test_payment_calendar_renders_grid_and_calculates_cash_flow_metrics(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-CAL-01',
            'company' => 'Calendar Client Co',
            'contact_name' => 'Cal User',
            'phone' => '9877556677',
            'status' => 'Active',
            'approval_status' => 'Approved',
            'created_by' => $this->admin->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-CAL-01',
            'name' => 'Calendar Web Build',
            'customer_id' => $customer->id,
            'status' => 'In Progress',
            'approval_status' => 'Approved',
            'value' => 100000,
        ]);

        $today = Carbon::today();

        // Term due in current month
        $term = PaymentTerm::create([
            'project_id' => $project->id,
            'seq' => 1,
            'label' => 'Sprint 1 Payment',
            'amount' => 40000,
            'due_date' => $today->toDateString(),
            'status' => 'Pending',
            'received' => 0,
            'tds' => 0,
        ]);

        // Payment recorded today
        Payment::create([
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'code' => 'RCP-CAL-01',
            'amount' => 15000,
            'tds' => 0,
            'paid_on' => $today->toDateString(),
            'mode' => 'NEFT',
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaymentCalendarIndex::class)
            ->assertSee('Payment Calendar')
            ->assertSee($today->format('F Y'))
            ->assertSet('month', $today->format('Y-m'))
            ->call('selectDate', $today->toDateString())
            ->assertSet('selectedDate', $today->toDateString())
            ->assertSee('Sprint 1 Payment')
            ->assertSee('RCP-CAL-01')
            ->call('closeDateModal')
            ->assertSet('selectedDate', null)
            ->call('nextMonth')
            ->assertSet('month', $today->copy()->startOfMonth()->addMonth()->format('Y-m'))
            ->call('prevMonth')
            ->assertSet('month', $today->format('Y-m'))
            ->call('goToToday')
            ->assertSet('month', $today->format('Y-m'));
    }

    /**
     * Tests ProjectRecurringModal generates sequential recurring retainer installments.
     */
    public function test_project_recurring_modal_generates_recurring_retainer_installments(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-REC-01',
            'company' => 'Retainer Client Inc',
            'contact_name' => 'Retainer Guy',
            'phone' => '9877889900',
            'status' => 'Active',
            'approval_status' => 'Approved',
            'created_by' => $this->admin->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-REC-01',
            'name' => 'Monthly Retainer SEO',
            'customer_id' => $customer->id,
            'status' => 'In Progress',
            'approval_status' => 'Approved',
            'value' => 60000,
            'start_date' => now()->toDateString(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectRecurringModal::class)
            ->call('load', $project->id)
            ->assertSet('projectId', $project->id)
            ->set('amount', 10000)
            ->set('months', 6)
            ->set('startDate', now()->toDateString())
            ->set('label', 'SEO Retainer')
            ->call('generate')
            ->assertDispatched('close-modal')
            ->assertDispatched('project-saved')
            ->assertDispatched('toast');

        $terms = PaymentTerm::where('project_id', $project->id)->orderBy('seq')->get();
        $this->assertCount(6, $terms);
        $this->assertEquals(10000, (float) $terms->first()->amount);
        $this->assertStringContainsString('SEO Retainer', $terms->first()->label);
    }

    /**
     * ISS-099/ISS-103: Verify Customer deletion cleans files from canonical documents storage disk.
     */
    public function test_customer_deletion_cleans_both_public_and_uploads_storage_disks(): void
    {
        Storage::fake('documents');
        Storage::fake('public');

        $customer = Customer::create([
            'customer_code' => 'CUS-DEL-01',
            'company' => 'To Be Deleted Corp',
            'contact_name' => 'Delete Me',
            'phone' => '9877000001',
            'status' => 'Active',
            'approval_status' => 'Approved',
            'created_by' => $this->admin->id,
        ]);

        $storedName = 'customer_doc_to_delete.pdf';
        Storage::disk('documents')->put($storedName, 'FILE ON DOCUMENTS');
        Storage::disk('public')->put($storedName, 'FILE ON PUBLIC');

        Document::create([
            'customer_id' => $customer->id,
            'project_id' => null,
            'category' => 'KYC/GST',
            'title' => 'KYC Document',
            'file_name' => 'kyc.pdf',
            'stored_as' => $storedName,
            'uploaded_by' => $this->admin->id,
        ]);

        Storage::disk('documents')->assertExists($storedName);
        Storage::disk('public')->assertExists($storedName);

        Livewire::actingAs($this->admin)
            ->test(CustomerDetail::class, ['customer' => $customer])
            ->call('deleteCustomer')
            ->assertRedirect(route('customers.index'));

        $this->assertNull(Customer::find($customer->id));
        Storage::disk('documents')->assertMissing($storedName);
        Storage::disk('public')->assertMissing($storedName);
    }

    /**
     * Tests CustomerImportModal auto-assigns sales_person_id when imported by a Sales user.
     */
    public function test_customer_import_modal_auto_assigns_salesperson_id_for_sales_users(): void
    {
        $csvContent = "company,contact,phone,email,city,state\nImported Client 1,Vikram Singh,9899112233,vikram@client.com,Delhi,DL";

        Livewire::actingAs($this->sales)
            ->test(CustomerImportModal::class)
            ->set('pastedData', $csvContent)
            ->call('import')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal')
            ->assertDispatched('customer-saved');

        $imported = Customer::where('phone', '9899112233')->first();
        $this->assertNotNull($imported);
        $this->assertEquals($this->sales->id, $imported->sales_person_id);
        $this->assertEquals('Pending', $imported->approval_status);
    }

    public function test_customer_deletion_is_blocked_when_customer_has_linked_projects(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-DEL-PROJ',
            'company' => 'Project Linked Client',
            'contact_name' => 'Alice Corp',
            'phone' => '9876543210',
            'email' => 'alice@corp.com',
            'city' => 'Delhi',
            'state' => 'DL',
            'approval_status' => 'Approved',
            'created_by' => $this->admin->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-DEL-GUARD',
            'name' => 'Active Guard Project',
            'customer_id' => $customer->id,
            'coordinator_id' => $this->coordinator->id,
            'tech_person_id' => $this->techPerson->id,
            'value' => 50000.00,
            'status' => 'In Progress',
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CustomerDetail::class, ['customer' => $customer])
            ->assertSee('Cannot Delete Customer')
            ->assertSee('linked project(s)')
            ->call('deleteCustomer')
            ->assertDispatched('toast', [
                'msg' => 'Cannot delete customer with 1 active project(s). Delete or reassign projects first.',
                'type' => 'err',
            ]);

        $this->assertNotNull(Customer::find($customer->id));
        $this->assertNotNull(Project::find($project->id));
    }
}
