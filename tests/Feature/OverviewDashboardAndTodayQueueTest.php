<?php

namespace Tests\Feature;

use App\Livewire\Components\DynamicCounter;
use App\Livewire\Dashboard\DashboardIndex;
use App\Livewire\Projects\MilestoneChaseModal;
use App\Livewire\Today\TodayWorkIndex;
use App\Models\Customer;
use App\Models\Milestone;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OverviewDashboardAndTodayQueueTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $coordinator;

    protected User $sales;

    protected Customer $customer;

    protected Project $project;

    protected Milestone $milestone;

    protected PaymentTerm $overdueTerm;

    protected PaymentTerm $upcomingTerm;

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
            'must_change' => false,
            'reporting_to' => $this->admin->id,
        ]);

        $this->coordinator = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
            'name' => 'Coord User',
            'email' => 'coord@test.com',
            'must_change' => false,
            'reporting_to' => $this->manager->id,
        ]);

        $this->sales = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Active',
            'name' => 'Sales User',
            'email' => 'sales@test.com',
            'must_change' => false,
            'reporting_to' => $this->coordinator->id,
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUS-DASH-1',
            'contact_name' => 'Apex Enterprises',
            'phone' => '9876543210',
            'state' => 'MH',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $this->project = Project::create([
            'project_code' => 'PJT-DASH-1',
            'customer_id' => $this->customer->id,
            'name' => 'Cloud ERP Suite',
            'value' => 100000.00,
            'cost' => 30000.00,
            'status' => 'In Progress',
            'health' => 'On Track',
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
            'start_date' => now()->subDays(30)->toDateString(),
            'delivery_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->milestone = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Phase 1 Delivery',
            'due_date' => now()->subDays(5)->toDateString(),
            'status' => 'Pending',
            'sort_order' => 1,
        ]);

        // Overdue installment
        $this->overdueTerm = PaymentTerm::create([
            'project_id' => $this->project->id,
            'milestone_id' => $this->milestone->id,
            'label' => 'Phase 1 Advance',
            'amount' => 40000.00,
            'received' => 10000.00,
            'tds' => 1000.00,
            'status' => 'Overdue',
            'seq' => 1,
            'percent' => 40.00,
            'due_date' => now()->subDays(5)->toDateString(),
            'grace_days' => 0,
        ]);

        // Upcoming installment within 7 days
        $this->upcomingTerm = PaymentTerm::create([
            'project_id' => $this->project->id,
            'label' => 'Phase 2 Progress',
            'amount' => 30000.00,
            'received' => 0.00,
            'tds' => 0.00,
            'status' => 'Pending',
            'seq' => 2,
            'percent' => 30.00,
            'due_date' => now()->addDays(3)->toDateString(),
            'grace_days' => 0,
        ]);

        // Payment record
        Payment::create([
            'code' => 'RCP-DASH-01',
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'term_id' => $this->overdueTerm->id,
            'amount' => 10000.00,
            'tds' => 1000.00,
            'mode' => 'NEFT',
            'paid_on' => now()->subDays(2)->toDateString(),
            'created_by' => $this->coordinator->id,
        ]);
    }

    public function test_overview_dashboard_renders_financial_kpis_and_metrics_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertStatus(200);

        // Assert company-wide executive financial summary labels are rendered
        $response->assertSee('Total Receivable');
        $response->assertSee('Realized Revenue');
        $response->assertSee('Balance Due');
        $response->assertSee('Overdue');
        $response->assertSee('Project Health');
        $response->assertSee('Collection Trend');
        $response->assertSee('Payment Ageing');
        $response->assertSee('Sales-wise Performance');

        // Test component rendering directly - uncluttered dashboard without individual task rows
        Livewire::actingAs($this->admin)
            ->test(DashboardIndex::class)
            ->assertSee('Total Receivable')
            ->assertSee('Realized Revenue')
            ->assertSee('Overdue')
            ->assertSee('Project Health')
            ->assertDontSee('Phase 1 Advance'); // Individual task belongs strictly in Today's Work
    }

    public function test_header_renders_add_customer_button_for_authorized_users(): void
    {
        $adminResponse = $this->actingAs($this->admin)->get('/dashboard');
        $adminResponse->assertSee('Add Customer');
        $adminResponse->assertSee('open-customer-modal');

        $salesResponse = $this->actingAs($this->sales)->get('/dashboard');
        $salesResponse->assertSee('Add Customer');
        $salesResponse->assertSee('open-customer-modal');
    }

    public function test_overview_dashboard_renders_team_highlights_for_manager(): void
    {
        $response = $this->actingAs($this->manager)->get('/dashboard');
        $response->assertStatus(200);

        // Assert manager team summary and coordinator workload
        $response->assertSee('Team Bookings');
        $response->assertSee('Team Collections');
        $response->assertSee('Coordinator Workload');
        $response->assertSee('Delivery Velocity');
        $response->assertSee($this->coordinator->name);
    }

    public function test_overview_dashboard_renders_execution_highlights_for_coordinator(): void
    {
        $response = $this->actingAs($this->coordinator)->get('/dashboard');
        $response->assertStatus(200);

        // Assert coordinator active projects, milestone delivery, and upcoming deliveries
        $response->assertSee('My Active Projects');
        $response->assertSee('Milestones Progress');
        $response->assertSee('Upcoming Deliveries');
        $response->assertSee($this->project->name);

        // Coordinator should NOT see manager-level or admin-level tables
        $response->assertDontSee('Coordinator Workload');
        $response->assertDontSee('Sales-wise Performance');
    }

    public function test_overview_dashboard_renders_deal_and_quota_highlights_for_sales(): void
    {
        $this->sales->update(['target' => 500000.00]);

        $response = $this->actingAs($this->sales)->get('/dashboard');
        $response->assertStatus(200);

        // Assert sales-tailored metrics
        $response->assertSee('Total Closed Bookings');
        $response->assertSee('Quota Target');
        $response->assertSee('Cash Inflow Realized');
        $response->assertSee('Client Projects Fulfillment');
        $response->assertSee('Recent Closed Deals');
        $response->assertSee('Sales Quota Target');
        $response->assertSee($this->project->name);

        // Sales does not see backend financial ledgers, ageing, or restricted schedules
        $response->assertDontSee('Payment Ageing');
        $response->assertDontSee(route('schedule.index'));
        $response->assertDontSee(route('payments.index'));
        $response->assertDontSee(route('today.index'));
    }

    public function test_today_work_is_accessible_to_authorized_roles_and_forbidden_to_sales(): void
    {
        $this->actingAs($this->admin)->get('/today-work')->assertStatus(200);
        $this->actingAs($this->manager)->get('/today-work')->assertStatus(200);
        $this->actingAs($this->coordinator)->get('/today-work')->assertStatus(200);

        $this->actingAs($this->sales)->get('/today-work')->assertStatus(403);
    }

    public function test_sidebar_displays_today_work_link_for_authorized_roles_and_hides_for_sales(): void
    {
        $adminView = $this->actingAs($this->admin)->get('/dashboard');
        $adminView->assertSee(route('today.index'));

        $coordView = $this->actingAs($this->coordinator)->get('/dashboard');
        $coordView->assertSee(route('today.index'));

        $salesView = $this->actingAs($this->sales)->get('/dashboard');
        $salesView->assertDontSee(route('today.index'));
    }

    public function test_today_work_displays_overdue_and_upcoming_threshold_installments(): void
    {
        Livewire::actingAs($this->coordinator)
            ->test(TodayWorkIndex::class)
            ->assertSee('Overdue Payments')
            ->assertSee('Phase 1 Advance')
            ->assertSee('Upcoming payments')
            ->assertSee('Phase 2 Progress')
            ->assertSee('Log Chase Note')
            ->assertSee('+ Payment');
    }

    public function test_milestone_chase_modal_triggered_by_term_id_creates_milestone_if_missing_and_saves_note(): void
    {
        // $this->upcomingTerm has no milestone_id
        $this->assertNull($this->upcomingTerm->milestone_id);

        $note = 'Client confirmed invoice was cleared by Accounts Payable.';
        $nextDate = now()->addDays(2)->toDateString();

        Livewire::actingAs($this->coordinator)
            ->test(MilestoneChaseModal::class)
            ->call('openModal', null, $this->upcomingTerm->id)
            ->set('chase_notes', $note)
            ->set('next_chase_date', $nextDate)
            ->call('save')
            ->assertDispatched('project-saved')
            ->assertDispatched('followup-saved');

        $this->upcomingTerm->refresh();
        $this->assertNotNull($this->upcomingTerm->milestone_id);

        $milestone = Milestone::find($this->upcomingTerm->milestone_id);
        $this->assertNotNull($milestone);
        $this->assertEquals($note, $milestone->chase_notes);
        $this->assertEquals($nextDate, $milestone->next_chase_date?->toDateString());

        $this->assertDatabaseHas('followups', [
            'customer_id' => $this->customer->id,
            'project_id' => $this->project->id,
            'type' => 'Note',
            'detail' => $note,
        ]);
    }

    public function test_dynamic_counter_reports_correct_today_overdue_count(): void
    {
        Livewire::actingAs($this->coordinator)
            ->test(DynamicCounter::class, ['type' => 'today'])
            ->assertSee('1');
    }
}
