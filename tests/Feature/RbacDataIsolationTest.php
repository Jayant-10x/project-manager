<?php

namespace Tests\Feature;

use App\Livewire\Audit\AuditIndex;
use App\Livewire\Customers\CustomerDetail;
use App\Livewire\Documents\DocumentIndex;
use App\Livewire\Followups\FollowupIndex;
use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Settings\SettingIndex;
use App\Livewire\Tasks\TaskIndex;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Followup;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RbacDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_scope_for_user_isolates_records_by_role(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $salesUserA = User::factory()->create(['role' => 'Sales']);
        $salesUserB = User::factory()->create(['role' => 'Sales']);
        $coordinator = User::factory()->create(['role' => 'Coordinator']);

        $custA = Customer::create([
            'customer_code' => 'CUS-A',
            'company' => 'Company Alpha',
            'status' => 'Active',
            'sales_person_id' => $salesUserA->id,
        ]);

        $custB = Customer::create([
            'customer_code' => 'CUS-B',
            'company' => 'Company Beta',
            'status' => 'Active',
            'sales_person_id' => $salesUserB->id,
        ]);

        $custC = Customer::create([
            'customer_code' => 'CUS-C',
            'company' => 'Company Gamma',
            'status' => 'Active',
            'created_by' => $coordinator->id,
        ]);

        // Admin sees all 3 customers
        $this->assertEquals(3, Customer::forUser($admin)->count());

        // Sales A only sees Customer A
        $salesACustomers = Customer::forUser($salesUserA)->pluck('id');
        $this->assertTrue($salesACustomers->contains($custA->id));
        $this->assertFalse($salesACustomers->contains($custB->id));
        $this->assertFalse($salesACustomers->contains($custC->id));

        // Sales B only sees Customer B
        $salesBCustomers = Customer::forUser($salesUserB)->pluck('id');
        $this->assertTrue($salesBCustomers->contains($custB->id));
        $this->assertFalse($salesBCustomers->contains($custA->id));

        // Coordinator sees Customer C
        $coordinatorCustomers = Customer::forUser($coordinator)->pluck('id');
        $this->assertTrue($coordinatorCustomers->contains($custC->id));
    }

    public function test_sales_user_cannot_view_unassigned_customer_detail(): void
    {
        $salesUser = User::factory()->create(['role' => 'Sales']);
        $otherSalesUser = User::factory()->create(['role' => 'Sales']);

        $customer = Customer::create([
            'customer_code' => 'CUS-OTHER',
            'company' => 'Other Company',
            'status' => 'Active',
            'sales_person_id' => $otherSalesUser->id,
        ]);

        $response = $this->actingAs($salesUser)->get(route('customers.show', $customer->id));
        $response->assertForbidden();
    }

    public function test_sales_user_can_view_assigned_customer_detail_without_financial_strip(): void
    {
        $salesUser = User::factory()->create(['role' => 'Sales']);

        $customer = Customer::create([
            'customer_code' => 'CUS-MINE',
            'company' => 'My Client Co',
            'status' => 'Active',
            'sales_person_id' => $salesUser->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-M01',
            'name' => 'Client Project',
            'customer_id' => $customer->id,
            'sales_person_id' => $salesUser->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 500000,
        ]);

        $response = $this->actingAs($salesUser)->get(route('customers.show', $customer->id));
        $response->assertOk();

        // Customer detail component test
        Livewire::actingAs($salesUser)
            ->test(CustomerDetail::class, ['customer' => $customer])
            ->assertOk()
            ->assertSee('My Client Co')
            ->assertDontSee('Collection progress')
            ->assertDontSee('Total Value');
    }

    public function test_customer_detail_payments_tab_is_guarded_against_sales_role(): void
    {
        $salesUser = User::factory()->create(['role' => 'Sales']);

        $customer = Customer::create([
            'customer_code' => 'CUS-TAB',
            'company' => 'Tab Guard Co',
            'status' => 'Active',
            'sales_person_id' => $salesUser->id,
        ]);

        Livewire::actingAs($salesUser)
            ->test(CustomerDetail::class, ['customer' => $customer])
            ->set('activeTab', 'payments')
            ->assertSet('activeTab', 'overview');
    }

    public function test_sales_user_cannot_access_financial_ledger_routes(): void
    {
        $salesUser = User::factory()->create(['role' => 'Sales']);

        // Payments index
        $this->actingAs($salesUser)->get(route('payments.index'))->assertForbidden();

        // Schedule index
        $this->actingAs($salesUser)->get(route('schedule.index'))->assertForbidden();

        // Reports index
        $this->actingAs($salesUser)->get(route('reports.index'))->assertForbidden();
    }

    public function test_coordinator_cannot_access_executive_reports_route(): void
    {
        $coordinator = User::factory()->create(['role' => 'Coordinator']);

        // Reports index is Admin/Manager only
        $this->actingAs($coordinator)->get(route('reports.index'))->assertForbidden();

        // But coordinator can access Payments and Schedule
        $this->actingAs($coordinator)->get(route('payments.index'))->assertOk();
        $this->actingAs($coordinator)->get(route('schedule.index'))->assertOk();
    }

    public function test_sales_user_cannot_see_financial_columns_in_project_index(): void
    {
        $salesUser = User::factory()->create(['role' => 'Sales']);
        $customer = Customer::create([
            'customer_code' => 'CUS-PROJ',
            'company' => 'Project Index Co',
            'status' => 'Active',
            'sales_person_id' => $salesUser->id,
        ]);

        Project::create([
            'project_code' => 'PJT-SEC01',
            'name' => 'Secured Column Project',
            'customer_id' => $customer->id,
            'sales_person_id' => $salesUser->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 750000,
        ]);

        Livewire::actingAs($salesUser)
            ->test(ProjectIndex::class)
            ->assertOk()
            ->assertSee('Secured Column Project')
            ->assertDontSee('Value')
            ->assertDontSee('Due');
    }

    public function test_document_and_followup_indexes_are_scoped_to_user_accessible_entities(): void
    {
        $salesUserA = User::factory()->create(['role' => 'Sales']);
        $salesUserB = User::factory()->create(['role' => 'Sales']);

        $custA = Customer::create([
            'customer_code' => 'CUS-DOC-A',
            'company' => 'Doc Company Alpha',
            'status' => 'Active',
            'sales_person_id' => $salesUserA->id,
        ]);

        $custB = Customer::create([
            'customer_code' => 'CUS-DOC-B',
            'company' => 'Doc Company Beta',
            'status' => 'Active',
            'sales_person_id' => $salesUserB->id,
        ]);

        Document::create([
            'customer_id' => $custA->id,
            'title' => 'Alpha Confidential NDA',
            'file_name' => 'nda_alpha.pdf',
            'stored_as' => 'documents/nda_alpha.pdf',
            'mime' => 'application/pdf',
            'size_kb' => 1024,
            'uploaded_by' => $salesUserA->id,
        ]);

        Document::create([
            'customer_id' => $custB->id,
            'title' => 'Beta Secret Proposal',
            'file_name' => 'proposal_beta.pdf',
            'stored_as' => 'documents/proposal_beta.pdf',
            'mime' => 'application/pdf',
            'size_kb' => 2048,
            'uploaded_by' => $salesUserB->id,
        ]);

        Followup::create([
            'customer_id' => $custA->id,
            'type' => 'Call',
            'summary' => 'Alpha Touchpoint Notes',
            'detail' => 'Called regarding contract renewal',
            'happened_on' => now()->toDateString(),
            'created_by' => $salesUserA->id,
        ]);

        Followup::create([
            'customer_id' => $custB->id,
            'type' => 'Call',
            'summary' => 'Beta Secret Touchpoint',
            'detail' => 'Private strategic meeting',
            'happened_on' => now()->toDateString(),
            'created_by' => $salesUserB->id,
        ]);

        // Sales A checks documents
        Livewire::actingAs($salesUserA)
            ->test(DocumentIndex::class)
            ->assertSee('Alpha Confidential NDA')
            ->assertDontSee('Beta Secret Proposal');

        // Sales A checks follow-ups
        Livewire::actingAs($salesUserA)
            ->test(FollowupIndex::class)
            ->assertSee('Alpha Touchpoint Notes')
            ->assertDontSee('Beta Secret Touchpoint');
    }

    public function test_activity_logs_are_isolated_by_role_and_downline(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Boss', 'role' => 'Admin', 'status' => 'Active']);
        $manager = User::factory()->create(['name' => 'Manager Mike', 'role' => 'Manager', 'status' => 'Active']);
        $subordinate = User::factory()->create(['name' => 'Subordinate Sam', 'role' => 'Coordinator', 'status' => 'Active', 'reporting_to' => $manager->id]);
        $unrelatedUser = User::factory()->create(['name' => 'Unrelated Uma', 'role' => 'Coordinator', 'status' => 'Active']);
        $salesUser = User::factory()->create(['name' => 'Sales Sally', 'role' => 'Sales', 'status' => 'Active', 'reporting_to' => $subordinate->id]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'Admin System Change',
            'entity' => 'System',
            'detail' => 'Changed global system parameters',
            'at_time' => now(),
        ]);

        ActivityLog::create([
            'user_id' => $manager->id,
            'action' => 'Manager Action',
            'entity' => 'Department',
            'detail' => 'Reviewed department budget',
            'at_time' => now(),
        ]);

        ActivityLog::create([
            'user_id' => $subordinate->id,
            'action' => 'Subordinate Milestone',
            'entity' => 'Milestone',
            'detail' => 'Updated sprint milestone',
            'at_time' => now(),
        ]);

        ActivityLog::create([
            'user_id' => $unrelatedUser->id,
            'action' => 'Unrelated Activity',
            'entity' => 'Lead',
            'detail' => 'Processed secret lead',
            'at_time' => now(),
        ]);

        ActivityLog::create([
            'user_id' => $salesUser->id,
            'action' => 'Sales Call Logged',
            'entity' => 'Customer',
            'detail' => 'Contacted potential customer',
            'at_time' => now(),
        ]);

        // 1. Admin sees everything
        Livewire::actingAs($admin)
            ->test(AuditIndex::class)
            ->assertSee('Admin System Change')
            ->assertSee('Manager Action')
            ->assertSee('Subordinate Milestone')
            ->assertSee('Unrelated Activity')
            ->assertSee('Sales Call Logged');

        // 2. Manager sees self + downline (Subordinate Sam and Sales Sally), but NOT Admin or Unrelated Uma
        Livewire::actingAs($manager)
            ->test(AuditIndex::class)
            ->assertSee('Manager Action')
            ->assertSee('Subordinate Milestone')
            ->assertSee('Sales Call Logged')
            ->assertDontSee('Admin System Change')
            ->assertDontSee('Unrelated Activity');

        // 3. Sales Sally sees only her own log
        Livewire::actingAs($salesUser)
            ->test(AuditIndex::class)
            ->assertSee('Sales Call Logged')
            ->assertDontSee('Manager Action')
            ->assertDontSee('Subordinate Milestone')
            ->assertDontSee('Admin System Change')
            ->assertDontSee('Unrelated Activity');

        // 4. Sales Sally attempting parameter tampering (setting user_id to Admin) is prevented
        Livewire::actingAs($salesUser)
            ->test(AuditIndex::class, ['user_id' => (string) $admin->id])
            ->assertDontSee('Admin System Change')
            ->assertSee('Sales Call Logged');
    }

    public function test_settings_users_card_is_hidden_from_sales_and_scoped_to_downline_for_managers(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Alex', 'role' => 'Admin', 'status' => 'Active']);
        $manager = User::factory()->create(['name' => 'Manager Mary', 'role' => 'Manager', 'status' => 'Active']);
        $downlineUser = User::factory()->create(['name' => 'Team Member Dan', 'role' => 'Coordinator', 'status' => 'Active', 'reporting_to' => $manager->id]);
        $unrelatedCoordinator = User::factory()->create(['name' => 'Other Coordinator Chris', 'role' => 'Coordinator', 'status' => 'Active']);
        $salesUser = User::factory()->create(['name' => 'Sales Sam', 'role' => 'Sales', 'status' => 'Active']);

        // 1. Sales user cannot see Users card at all
        Livewire::actingAs($salesUser)
            ->test(SettingIndex::class)
            ->assertDontSee('Team Member Dan')
            ->assertDontSee('Other Coordinator Chris')
            ->assertDontSee('Admin Alex')
            ->assertDontSee('accounts');

        // 2. Manager Mary sees self and Team Member Dan, but NOT Other Coordinator Chris or Admin Alex in Users card
        Livewire::actingAs($manager)
            ->test(SettingIndex::class)
            ->assertSee('Manager Mary')
            ->assertSee('Team Member Dan')
            ->assertDontSee('Other Coordinator Chris')
            ->assertDontSee('Admin Alex');

        // 3. Admin Alex sees all users
        Livewire::actingAs($admin)
            ->test(SettingIndex::class)
            ->assertSee('Manager Mary')
            ->assertSee('Team Member Dan')
            ->assertSee('Other Coordinator Chris')
            ->assertSee('Sales Sam')
            ->assertSee('Admin Alex');
    }

    public function test_task_assignment_enforces_downline_isolation_and_rejects_unauthorized_assignment(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'status' => 'Active']);
        $manager = User::factory()->create(['role' => 'Manager', 'status' => 'Active']);
        $downlineUser = User::factory()->create(['role' => 'Coordinator', 'status' => 'Active', 'reporting_to' => $manager->id]);
        $unrelatedUser = User::factory()->create(['role' => 'Coordinator', 'status' => 'Active']);
        $salesUser = User::factory()->create(['role' => 'Sales', 'status' => 'Active']);

        // 1. Manager can assign task to their downline member
        Livewire::actingAs($manager)
            ->test(TaskIndex::class)
            ->set('title', 'Valid Downline Task')
            ->set('priority', 'High')
            ->set('assigned_to', $downlineUser->id)
            ->call('addTask')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Valid Downline Task',
            'assigned_to' => $downlineUser->id,
            'created_by' => $manager->id,
        ]);

        // 2. Manager cannot assign task to unrelated user outside downline
        Livewire::actingAs($manager)
            ->test(TaskIndex::class)
            ->set('title', 'Unauthorized Assignment Task')
            ->set('priority', 'High')
            ->set('assigned_to', $unrelatedUser->id)
            ->call('addTask')
            ->assertHasErrors(['assigned_to']);

        // 3. Sales user assignment is forced to self
        Livewire::actingAs($salesUser)
            ->test(TaskIndex::class)
            ->set('title', 'Sales Self Task')
            ->set('priority', 'Medium')
            ->set('assigned_to', $admin->id) // attempting to assign to admin
            ->call('addTask')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Sales Self Task',
            'assigned_to' => $salesUser->id,
            'created_by' => $salesUser->id,
        ]);

        // 4. Admin can assign to anyone
        Livewire::actingAs($admin)
            ->test(TaskIndex::class)
            ->set('title', 'Admin Delegated Task')
            ->set('priority', 'Urgent')
            ->set('assigned_to', $unrelatedUser->id)
            ->call('addTask')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Admin Delegated Task',
            'assigned_to' => $unrelatedUser->id,
            'created_by' => $admin->id,
        ]);
    }
}
