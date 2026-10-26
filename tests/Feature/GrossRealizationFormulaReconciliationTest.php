<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\DashboardIndex;
use App\Livewire\Reports\ReportIndex;
use App\Livewire\Schedule\ScheduleIndex;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use App\Services\MoneyCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GrossRealizationFormulaReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_money_calculation_service_methods_reconcile_to_100_percent(): void
    {
        $service = app(MoneyCalculationService::class);

        $scheduled = 100000.0;
        $cash = 80000.0;
        $tds = 10000.0;

        $grossSettled = $service->calculateGrossSettled($cash, $tds);
        $due = $service->calculateBalanceDue($scheduled, $cash, $tds);
        $rate = $service->calculateCollectionRate($scheduled, $cash, $tds);

        $this->assertEquals(90000.0, $grossSettled);
        $this->assertEquals(10000.0, $due);
        $this->assertEquals(90.0, $rate);

        // Clean reconciliation to 100%
        $this->assertEquals($scheduled, $grossSettled + $due);
        $this->assertEquals(100.0, $rate + (($due / $scheduled) * 100));
    }

    public function test_project_model_money_attribute_uses_gross_settled_and_reconciles_cleanly(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $customer = Customer::create([
            'customer_code' => 'CUS-100',
            'company' => 'Stark Industries',
            'contact_name' => 'Tony Stark',
            'status' => 'Active',
            'created_by' => $user->id,
        ]);

        $project = Project::create([
            'project_code' => 'PRJ-100',
            'name' => 'Arc Reactor Site',
            'customer_id' => $customer->id,
            'contract_value' => 200000.0,
            'status' => 'In Progress',
            'health' => 'On Track',
            'created_by' => $user->id,
        ]);

        $term = PaymentTerm::create([
            'project_id' => $project->id,
            'seq' => 1,
            'label' => 'Phase 1 Milestone',
            'amount' => 200000.0,
            'received' => 150000.0,
            'tds' => 20000.0,
            'status' => 'Partial',
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        Payment::create([
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'payment_term_id' => $term->id,
            'code' => 'REC-001',
            'amount' => 150000.0,
            'tds' => 20000.0,
            'mode' => 'NEFT',
            'paid_on' => now()->toDateString(),
            'recorded_by' => $user->id,
        ]);

        $project->refresh();
        $money = $project->money;

        // Gross Settled Revenue = 150,000 Cash + 20,000 TDS = 170,000
        $this->assertEquals(170000.0, $money['received']);
        $this->assertEquals(170000.0, $money['settled']);
        $this->assertEquals(150000.0, $money['cash']);
        $this->assertEquals(20000.0, $money['tds']);

        // Due = max(0, 200,000 - 150,000 - 20,000) = 30,000
        $this->assertEquals(30000.0, $money['due']);

        // Clean reconciliation to 100% of scheduled
        $this->assertEquals($money['scheduled'], $money['received'] + $money['due']);
    }

    public function test_dashboard_displays_gross_realized_revenue_with_cash_and_tds_breakdown(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $customer = Customer::create([
            'customer_code' => 'CUS-101',
            'company' => 'Wayne Enterprises',
            'contact_name' => 'Bruce Wayne',
            'status' => 'Active',
            'created_by' => $admin->id,
        ]);

        $project = Project::create([
            'project_code' => 'PRJ-101',
            'name' => 'Batcave Security Upgrade',
            'customer_id' => $customer->id,
            'contract_value' => 100000.0,
            'status' => 'In Progress',
            'health' => 'On Track',
            'created_by' => $admin->id,
        ]);

        $term = PaymentTerm::create([
            'project_id' => $project->id,
            'seq' => 1,
            'label' => 'Initial Installment',
            'amount' => 100000.0,
            'received' => 70000.0,
            'tds' => 10000.0,
            'status' => 'Partial',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        Payment::create([
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'payment_term_id' => $term->id,
            'code' => 'REC-101',
            'amount' => 70000.0,
            'tds' => 10000.0,
            'mode' => 'RTGS',
            'paid_on' => now()->toDateString(),
            'recorded_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(DashboardIndex::class)
            ->assertSee('TDS Credit')
            ->assertSee('80% settled');
    }

    public function test_reports_and_schedule_use_gross_settled_revenue(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $customer = Customer::create([
            'customer_code' => 'CUS-102',
            'company' => 'Oscorp Labs',
            'contact_name' => 'Norman Osborn',
            'status' => 'Active',
            'created_by' => $admin->id,
        ]);

        $project = Project::create([
            'project_code' => 'PRJ-102',
            'name' => 'Glider Propulsion',
            'customer_id' => $customer->id,
            'contract_value' => 50000.0,
            'status' => 'In Progress',
            'health' => 'On Track',
            'created_by' => $admin->id,
        ]);

        $term = PaymentTerm::create([
            'project_id' => $project->id,
            'seq' => 1,
            'label' => 'Milestone Advance',
            'amount' => 50000.0,
            'received' => 45000.0,
            'tds' => 5000.0,
            'status' => 'Paid',
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        Payment::create([
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'payment_term_id' => $term->id,
            'code' => 'REC-102',
            'amount' => 45000.0,
            'tds' => 5000.0,
            'mode' => 'NEFT',
            'paid_on' => now()->toDateString(),
            'recorded_by' => $admin->id,
        ]);

        // In reports, collection rate should be 100% since 45k + 5k = 50k / 50k
        Livewire::actingAs($admin)
            ->test(ReportIndex::class)
            ->assertSee('100%')
            ->assertSee('TDS Credit');

        // In schedule, received KPI card should display 50k
        Livewire::actingAs($admin)
            ->test(ScheduleIndex::class)
            ->assertSee('TDS');
    }
}
