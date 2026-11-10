<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\DashboardIndex;
use App\Livewire\Reports\ReportIndex;
use App\Livewire\Tasks\TaskIndex;
use App\Livewire\Today\TodayWorkIndex;
use App\Models\Customer;
use App\Models\Followup;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FollowupMessageAndKpiCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_followup_call_displays_message_detail_in_today_work(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $customer = Customer::create([
            'customer_code' => 'CUS-001',
            'company' => 'Acme Corp',
            'contact_name' => 'Alice Smith',
            'email' => 'alice@acme.com',
            'phone' => '+91 9876543210',
            'status' => 'Active',
            'created_by' => $admin->id,
        ]);

        $project = Project::create([
            'project_code' => 'PRJ-001',
            'name' => 'Acme Redesign',
            'customer_id' => $customer->id,
            'contract_value' => 50000,
            'status' => 'In Progress',
            'health' => 'On Track',
            'created_by' => $admin->id,
        ]);

        Followup::create([
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'type' => 'Call',
            'summary' => 'Payment Clearance Discussion',
            'detail' => 'Client confirmed finance team cleared invoice #102 for release tomorrow morning.',
            'happened_on' => now(),
            'next_date' => now()->addDay()->toDateString(),
            'next_note' => 'Verify bank receipt NEFT reference',
            'created_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(TodayWorkIndex::class)
            ->assertSee('Acme Corp')
            ->assertSee('Client confirmed finance team cleared invoice #102 for release tomorrow morning.')
            ->assertSee('+91 9876543210')
            ->assertSee('Verify bank receipt NEFT reference')
            ->assertSee(route('followups.index'))
            ->assertSee(route('tasks.index'));
    }

    public function test_followup_call_displays_message_detail_in_my_tasks(): void
    {
        $user = User::factory()->create(['role' => 'Coordinator']);
        $customer = Customer::create([
            'customer_code' => 'CUS-002',
            'company' => 'Beta Logistics',
            'contact_name' => 'Bob Jones',
            'email' => 'bob@beta.com',
            'phone' => '+91 9123456789',
            'status' => 'Active',
            'created_by' => $user->id,
        ]);

        Followup::create([
            'customer_id' => $customer->id,
            'type' => 'Call',
            'summary' => 'Contract Extension Meeting',
            'detail' => 'Customer requested an additional development sprint for the mobile view.',
            'happened_on' => now(),
            'next_date' => now()->addDays(2)->toDateString(),
            'next_note' => 'Send revised sprint estimate',
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskIndex::class)
            ->assertSee('Beta Logistics')
            ->assertSee('Customer requested an additional development sprint for the mobile view.')
            ->assertSee('+91 9123456789')
            ->assertSee('Send revised sprint estimate')
            ->assertSee('Pending Tasks')
            ->assertSee('Completed')
            ->assertSee('Follow-up Calls');
    }

    public function test_dashboard_kpi_cards_have_valid_target_links_for_sales(): void
    {
        $sales = User::factory()->create([
            'role' => 'Sales',
            'target' => 500000,
        ]);

        Livewire::actingAs($sales)
            ->test(DashboardIndex::class)
            ->assertSee(route('projects.index'))
            ->assertSee(route('customers.index'));
    }

    public function test_reports_kpi_cards_have_valid_target_links_for_coordinator(): void
    {
        $coordinator = User::factory()->create(['role' => 'Coordinator']);

        Livewire::actingAs($coordinator)
            ->test(ReportIndex::class)
            ->assertSee(route('schedule.index'))
            ->assertSee(route('payments.index'))
            ->assertSee(route('projects.index'))
            ->assertSee(route('today.index'));
    }

    public function test_coordinator_dashboard_has_payment_cards_with_clickable_links(): void
    {
        $coordinator = User::factory()->create(['role' => 'Coordinator']);
        $customer = Customer::create([
            'customer_code' => 'CUS-COORD-01',
            'company' => 'Starlight Media',
            'contact_name' => 'Sara Connor',
            'status' => 'Active',
            'created_by' => $coordinator->id,
        ]);

        $project = Project::create([
            'project_code' => 'PRJ-COORD-01',
            'name' => 'Streaming Platform',
            'customer_id' => $customer->id,
            'coordinator_id' => $coordinator->id,
            'contract_value' => 80000.0,
            'status' => 'In Progress',
            'health' => 'On Track',
            'created_by' => $coordinator->id,
        ]);

        Livewire::actingAs($coordinator)
            ->test(DashboardIndex::class)
            ->assertSee('Total Receivable')
            ->assertSee('Received')
            ->assertSee('Due')
            ->assertSee('Overdue')
            ->assertSee('Active Projects')
            ->assertSee(route('schedule.index'))
            ->assertSee(route('payments.index'))
            ->assertSee(route('today.index'))
            ->assertSee(route('projects.index'));
    }

    public function test_sales_dashboard_has_due_and_overdue_cards_with_clickable_links(): void
    {
        $sales = User::factory()->create([
            'role' => 'Sales',
            'target' => 500000,
        ]);

        Livewire::actingAs($sales)
            ->test(DashboardIndex::class)
            ->assertSee('Total Receivable')
            ->assertSee('Received')
            ->assertSee('Due')
            ->assertSee('Overdue')
            ->assertSee('Customers')
            ->assertSee(route('projects.index'))
            ->assertSee(route('customers.index'));
    }

    public function test_my_tasks_displays_overdue_card_and_milestone_chases(): void
    {
        $coordinator = User::factory()->create(['role' => 'Coordinator']);
        $customer = Customer::create([
            'customer_code' => 'CUS-COORD-02',
            'company' => 'Apex Global',
            'contact_name' => 'David Miller',
            'status' => 'Active',
            'created_by' => $coordinator->id,
        ]);

        $project = Project::create([
            'project_code' => 'PRJ-COORD-02',
            'name' => 'Logistics Tracking',
            'customer_id' => $customer->id,
            'coordinator_id' => $coordinator->id,
            'contract_value' => 60000.0,
            'status' => 'In Progress',
            'health' => 'On Track',
            'created_by' => $coordinator->id,
        ]);

        Milestone::create([
            'project_id' => $project->id,
            'name' => 'API Integration Phase',
            'done' => false,
            'next_chase_date' => now()->addDays(3)->toDateString(),
            'chase_notes' => 'Awaiting API keys from client security team.',
        ]);

        Livewire::actingAs($coordinator)
            ->test(TaskIndex::class)
            ->assertSee('Pending Tasks')
            ->assertSee('Completed')
            ->assertSee('Overdue Tasks')
            ->assertSee('Upcoming Milestone Chases')
            ->assertSee('API Integration Phase')
            ->assertSee('Awaiting API keys from client security team.');
    }
}
