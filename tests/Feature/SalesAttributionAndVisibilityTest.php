<?php

namespace Tests\Feature;

use App\Livewire\Projects\ProjectDetail;
use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Sales\SalesIndex;
use App\Livewire\Settings\UserModal;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SalesAttributionAndVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_persons_table_dropped_and_sales_person_model_removed(): void
    {
        $this->assertFalse(Schema::hasTable('sales_persons'));
        $this->assertFileDoesNotExist(app_path('Models/SalesPerson.php'));
        $this->assertFalse(class_exists('App\Models\SalesPerson', false));
    }

    public function test_user_modal_saves_target_for_sales_role_and_dispatches_events_without_redirect(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        Livewire::actingAs($admin)
            ->test(UserModal::class)
            ->call('openModal', null, 'Sales')
            ->set('name', 'Vikram Patel')
            ->set('email', 'vikram.sales@10xagency.in')
            ->set('phone', '+91 9876543210')
            ->set('role', 'Sales')
            ->set('target', 750000.00)
            ->set('status', 'Active')
            ->set('password', 'SecurePass123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('user-saved')
            ->assertDispatched('sales-saved')
            ->assertDispatched('close-modal', 'user-modal')
            ->assertNoRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'Vikram Patel',
            'email' => 'vikram.sales@10xagency.in',
            'role' => 'Sales',
            'target' => 750000.00,
        ]);
    }

    public function test_user_modal_updates_target_for_existing_sales_user(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $salesUser = User::factory()->create([
            'role' => 'Sales',
            'target' => 300000.00,
        ]);

        Livewire::actingAs($admin)
            ->test(UserModal::class)
            ->call('openModal', $salesUser->id)
            ->assertSet('target', 300000.00)
            ->set('target', 900000.00)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('user-saved')
            ->assertDispatched('sales-saved');

        $this->assertEquals(900000.00, $salesUser->fresh()->target);
    }

    public function test_project_model_has_sales_person_relationship(): void
    {
        $salesUser = User::factory()->create(['role' => 'Sales', 'name' => 'John Seller']);
        $customer = Customer::create(['customer_code' => 'CUS-001', 'company' => 'Acme Corp', 'status' => 'Active']);
        $project = Project::create([
            'project_code' => 'PJT-001',
            'name' => 'Project Alpha',
            'customer_id' => $customer->id,
            'sales_person_id' => $salesUser->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 500000,
        ]);

        $this->assertNotNull($project->salesPerson);
        $this->assertEquals($salesUser->id, $project->salesPerson->id);
        $this->assertEquals('John Seller', $project->salesPerson->name);
    }

    public function test_sales_user_can_access_attributed_project_details(): void
    {
        $salesUser = User::factory()->create(['role' => 'Sales']);
        $coordinator = User::factory()->create(['role' => 'Coordinator']);
        $customer = Customer::create(['customer_code' => 'CUS-002', 'company' => 'Beta Ltd', 'status' => 'Active']);
        $project = Project::create([
            'project_code' => 'PJT-002',
            'name' => 'Project Beta',
            'customer_id' => $customer->id,
            'coordinator_id' => $coordinator->id,
            'sales_person_id' => $salesUser->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 200000,
        ]);

        $response = $this->actingAs($salesUser)->get(route('projects.show', $project->id));
        $response->assertOk();
    }

    public function test_sales_user_cannot_access_unattributed_project_details(): void
    {
        $salesUser = User::factory()->create(['role' => 'Sales']);
        $otherSalesUser = User::factory()->create(['role' => 'Sales']);
        $coordinator = User::factory()->create(['role' => 'Coordinator']);
        $customer = Customer::create(['customer_code' => 'CUS-003', 'company' => 'Gamma Inc', 'status' => 'Active']);
        $project = Project::create([
            'project_code' => 'PJT-003',
            'name' => 'Project Gamma',
            'customer_id' => $customer->id,
            'coordinator_id' => $coordinator->id,
            'sales_person_id' => $otherSalesUser->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 300000,
        ]);

        $response = $this->actingAs($salesUser)->get(route('projects.show', $project->id));
        $response->assertForbidden();
    }

    public function test_sales_user_active_tab_clamps_away_from_schedule_and_payments(): void
    {
        $salesUser = User::factory()->create(['role' => 'Sales']);
        $customer = Customer::create(['customer_code' => 'CUS-004', 'company' => 'Delta Co', 'status' => 'Active']);
        $project = Project::create([
            'project_code' => 'PJT-004',
            'name' => 'Project Delta',
            'customer_id' => $customer->id,
            'sales_person_id' => $salesUser->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 400000,
        ]);

        Livewire::actingAs($salesUser)
            ->withQueryParams(['tab' => 'schedule'])
            ->test(ProjectDetail::class, ['project' => $project])
            ->assertSet('activeTab', 'overview')
            ->set('activeTab', 'schedule')
            ->assertSet('activeTab', 'overview')
            ->set('activeTab', 'payments')
            ->assertSet('activeTab', 'overview')
            ->set('activeTab', 'milestones')
            ->assertSet('activeTab', 'milestones');
    }

    public function test_project_detail_view_hides_financials_for_sales_role(): void
    {
        $salesUser = User::factory()->create(['role' => 'Sales', 'name' => 'Vikram Seller']);
        $customer = Customer::create(['customer_code' => 'CUS-005', 'company' => 'Epsilon LLC', 'status' => 'Active']);
        $project = Project::create([
            'project_code' => 'PJT-005',
            'name' => 'Project Epsilon',
            'customer_id' => $customer->id,
            'sales_person_id' => $salesUser->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 500000,
        ]);

        PaymentTerm::create([
            'project_id' => $project->id,
            'seq' => 1,
            'label' => 'Advance Deposit',
            'amount' => 100000,
            'status' => 'Pending',
        ]);

        Livewire::actingAs($salesUser)
            ->test(ProjectDetail::class, ['project' => $project])
            ->assertDontSee('Payment schedule')
            ->assertDontSee('Receipts')
            ->assertDontSee('Record payment')
            ->assertSee('Vikram Seller')
            ->assertSee('Overview')
            ->assertSee('Milestones');
    }

    public function test_sales_user_cannot_delete_term_or_payment(): void
    {
        $salesUser = User::factory()->create(['role' => 'Sales']);
        $customer = Customer::create(['customer_code' => 'CUS-006', 'company' => 'Zeta Corp', 'status' => 'Active']);
        $project = Project::create([
            'project_code' => 'PJT-006',
            'name' => 'Project Zeta',
            'customer_id' => $customer->id,
            'sales_person_id' => $salesUser->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 600000,
        ]);

        $term = PaymentTerm::create([
            'project_id' => $project->id,
            'seq' => 1,
            'label' => 'Milestone 1',
            'amount' => 50000,
            'status' => 'Pending',
        ]);

        $payment = Payment::create([
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'code' => 'RCP-9999',
            'amount' => 25000,
            'tds' => 0,
            'paid_on' => now()->toDateString(),
            'mode' => 'NEFT',
            'created_by' => $salesUser->id,
        ]);

        Livewire::actingAs($salesUser)
            ->test(ProjectDetail::class, ['project' => $project])
            ->call('deleteTerm', $term->id)
            ->assertStatus(403);

        Livewire::actingAs($salesUser)
            ->test(ProjectDetail::class, ['project' => $project])
            ->call('deletePayment', $payment->id)
            ->assertStatus(403);
    }

    public function test_project_index_filters_by_sales_person_id(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $sales1 = User::factory()->create(['role' => 'Sales', 'name' => 'Alice Rep']);
        $sales2 = User::factory()->create(['role' => 'Sales', 'name' => 'Bob Rep']);
        $customer = Customer::create(['customer_code' => 'CUS-007', 'company' => 'Eta Co', 'status' => 'Active']);

        $proj1 = Project::create([
            'project_code' => 'PJT-007',
            'name' => 'Alice Assigned App',
            'customer_id' => $customer->id,
            'sales_person_id' => $sales1->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 100000,
        ]);

        $proj2 = Project::create([
            'project_code' => 'PJT-008',
            'name' => 'Bob Assigned Portal',
            'customer_id' => $customer->id,
            'sales_person_id' => $sales2->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 200000,
        ]);

        Livewire::actingAs($admin)
            ->test(ProjectIndex::class)
            ->set('sales_person_id', (string) $sales1->id)
            ->assertSee('Alice Assigned App')
            ->assertDontSee('Bob Assigned Portal')
            ->call('clearFilters')
            ->assertSet('sales_person_id', '')
            ->assertSee('Alice Assigned App')
            ->assertSee('Bob Assigned Portal');
    }

    public function test_manager_with_sales_downline_can_view_projects_via_scope_for_user(): void
    {
        $manager = User::factory()->create(['role' => 'Manager']);
        $salesRep = User::factory()->create([
            'role' => 'Sales',
            'reporting_to' => $manager->id,
        ]);
        $customer = Customer::create(['customer_code' => 'CUS-009', 'company' => 'Theta Co', 'status' => 'Active']);

        $project = Project::create([
            'project_code' => 'PJT-009',
            'name' => 'Downline Project',
            'customer_id' => $customer->id,
            'sales_person_id' => $salesRep->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 350000,
        ]);

        $this->assertTrue(Project::forUser($manager)->where('id', $project->id)->exists());

        $response = $this->actingAs($manager)->get(route('projects.show', $project->id));
        $response->assertOk();
    }

    public function test_sales_index_projects_link_includes_sales_person_id_query_parameter(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $salesRep = User::factory()->create([
            'role' => 'Sales',
            'name' => 'Priya Sharma',
        ]);

        Livewire::actingAs($admin)
            ->test(SalesIndex::class)
            ->assertSeeHtml(route('projects.index', ['sales_person_id' => $salesRep->id]));
    }

    public function test_user_modal_validates_target_must_be_positive(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        Livewire::actingAs($admin)
            ->test(UserModal::class)
            ->call('openModal', null, 'Sales')
            ->set('name', 'Invalid Target User')
            ->set('email', 'invalid.target@10xagency.in')
            ->set('role', 'Sales')
            ->set('target', -500.00)
            ->set('status', 'Active')
            ->set('password', 'ValidPass123')
            ->call('save')
            ->assertHasErrors(['target' => 'min']);
    }

    public function test_user_modal_resets_target_to_zero_when_role_is_not_sales(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        Livewire::actingAs($admin)
            ->test(UserModal::class)
            ->call('openModal', null, 'Coordinator')
            ->set('name', 'Dev Coordinator')
            ->set('email', 'coord@10xagency.in')
            ->set('role', 'Coordinator')
            ->set('target', 500000.00)
            ->set('status', 'Active')
            ->set('password', 'ValidPass123')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'coord@10xagency.in')->firstOrFail();
        $this->assertEquals(0.00, (float) $user->target);
    }

    public function test_project_index_searches_by_salesperson_name(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $sales = User::factory()->create(['role' => 'Sales', 'name' => 'Target Salesperson UniqueName']);
        $customer = Customer::create(['customer_code' => 'CUS-010', 'company' => 'Search Co', 'status' => 'Active']);

        Project::create([
            'project_code' => 'PJT-010',
            'name' => 'Unique Salesperson Project',
            'customer_id' => $customer->id,
            'sales_person_id' => $sales->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 250000,
        ]);

        Livewire::actingAs($admin)
            ->test(ProjectIndex::class)
            ->set('search', 'UniqueName')
            ->assertSee('Unique Salesperson Project');
    }

    public function test_project_detail_handles_null_sales_person_gracefully(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $customer = Customer::create(['customer_code' => 'CUS-011', 'company' => 'No Sales Co', 'status' => 'Active']);

        $project = Project::create([
            'project_code' => 'PJT-011',
            'name' => 'No Salesperson Project',
            'customer_id' => $customer->id,
            'sales_person_id' => null,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 150000,
        ]);

        Livewire::actingAs($admin)
            ->test(ProjectDetail::class, ['project' => $project])
            ->assertOk()
            ->assertSee('Salesperson')
            ->assertSee('—');
    }

    public function test_non_admin_cannot_save_in_user_modal(): void
    {
        $salesUser = User::factory()->create(['role' => 'Sales']);

        Livewire::actingAs($salesUser)
            ->test(UserModal::class)
            ->call('openModal', null, 'Sales')
            ->set('name', 'Hacker Attempt')
            ->set('email', 'hacker@10xagency.in')
            ->set('password', 'HackerPass123')
            ->call('save')
            ->assertDispatched('toast', ['msg' => 'Only administrators can manage users.', 'type' => 'err']);

        $this->assertDatabaseMissing('users', ['email' => 'hacker@10xagency.in']);
    }
}
