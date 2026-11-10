<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\DashboardIndex;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardDateFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Customer $customer;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'must_change' => false,
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUS-FILTER-1',
            'contact_name' => 'Stark Industries',
            'status' => 'Active',
            'created_by' => $this->admin->id,
        ]);

        $this->project = Project::create([
            'project_code' => 'PJT-FILTER-1',
            'customer_id' => $this->customer->id,
            'name' => 'Avionics Dashboard Suite',
            'value' => 200000.00,
            'status' => 'In Progress',
            'health' => 'On Track',
            'start_date' => now()->subMonths(3)->toDateString(),
            'delivery_date' => now()->addMonths(3)->toDateString(),
        ]);
    }

    public function test_dashboard_defaults_to_today_filter(): void
    {
        $todayStr = Carbon::today()->toDateString();

        $component = Livewire::actingAs($this->admin)
            ->test(DashboardIndex::class);

        $this->assertEquals('today', $component->get('datePreset'));
        $this->assertEquals($todayStr, $component->get('dateFrom'));
        $this->assertEquals($todayStr, $component->get('dateTo'));
        $component->assertSee('Period:')
            ->assertSee('Today');
    }

    public function test_dashboard_date_filter_presets_calculate_correct_ranges(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(DashboardIndex::class);

        // Yesterday
        $component->call('selectPreset', 'yesterday');
        $this->assertEquals('yesterday', $component->get('datePreset'));
        $this->assertEquals(Carbon::yesterday()->toDateString(), $component->get('dateFrom'));
        $this->assertEquals(Carbon::yesterday()->toDateString(), $component->get('dateTo'));

        // Last 7 Days
        $component->call('selectPreset', 'last_7_days');
        $this->assertEquals('last_7_days', $component->get('datePreset'));
        $this->assertEquals(Carbon::today()->subDays(6)->toDateString(), $component->get('dateFrom'));
        $this->assertEquals(Carbon::today()->toDateString(), $component->get('dateTo'));

        // Last 30 Days
        $component->call('selectPreset', 'last_30_days');
        $this->assertEquals('last_30_days', $component->get('datePreset'));
        $this->assertEquals(Carbon::today()->subDays(29)->toDateString(), $component->get('dateFrom'));
        $this->assertEquals(Carbon::today()->toDateString(), $component->get('dateTo'));

        // This Week
        $component->call('selectPreset', 'this_week');
        $this->assertEquals('this_week', $component->get('datePreset'));
        $this->assertEquals(Carbon::now()->startOfWeek()->toDateString(), $component->get('dateFrom'));
        $this->assertEquals(Carbon::now()->endOfWeek()->toDateString(), $component->get('dateTo'));

        // Last Week
        $component->call('selectPreset', 'last_week');
        $this->assertEquals('last_week', $component->get('datePreset'));
        $this->assertEquals(Carbon::now()->subWeek()->startOfWeek()->toDateString(), $component->get('dateFrom'));
        $this->assertEquals(Carbon::now()->subWeek()->endOfWeek()->toDateString(), $component->get('dateTo'));

        // This Month
        $component->call('selectPreset', 'this_month');
        $this->assertEquals('this_month', $component->get('datePreset'));
        $this->assertEquals(Carbon::now()->startOfMonth()->toDateString(), $component->get('dateFrom'));
        $this->assertEquals(Carbon::now()->endOfMonth()->toDateString(), $component->get('dateTo'));

        // Last Month
        $component->call('selectPreset', 'last_month');
        $this->assertEquals('last_month', $component->get('datePreset'));
        $this->assertEquals(Carbon::now()->subMonthNoOverflow()->startOfMonth()->toDateString(), $component->get('dateFrom'));
        $this->assertEquals(Carbon::now()->subMonthNoOverflow()->endOfMonth()->toDateString(), $component->get('dateTo'));

        // This Quarter
        $component->call('selectPreset', 'this_quarter');
        $this->assertEquals('this_quarter', $component->get('datePreset'));
        $this->assertEquals(Carbon::now()->startOfQuarter()->toDateString(), $component->get('dateFrom'));
        $this->assertEquals(Carbon::now()->endOfQuarter()->toDateString(), $component->get('dateTo'));

        // Last Quarter
        $component->call('selectPreset', 'last_quarter');
        $this->assertEquals('last_quarter', $component->get('datePreset'));
        $this->assertEquals(Carbon::now()->subQuarter()->startOfQuarter()->toDateString(), $component->get('dateFrom'));
        $this->assertEquals(Carbon::now()->subQuarter()->endOfQuarter()->toDateString(), $component->get('dateTo'));

        // Year to Date (YTD)
        $component->call('selectPreset', 'ytd');
        $this->assertEquals('ytd', $component->get('datePreset'));
        $this->assertEquals(Carbon::now()->startOfYear()->toDateString(), $component->get('dateFrom'));
        $this->assertEquals(Carbon::today()->toDateString(), $component->get('dateTo'));

        // Last Year
        $component->call('selectPreset', 'last_year');
        $this->assertEquals('last_year', $component->get('datePreset'));
        $this->assertEquals(Carbon::now()->subYear()->startOfYear()->toDateString(), $component->get('dateFrom'));
        $this->assertEquals(Carbon::now()->subYear()->endOfYear()->toDateString(), $component->get('dateTo'));

        // All Time
        $component->call('selectPreset', 'all_time');
        $this->assertEquals('all_time', $component->get('datePreset'));
        $this->assertNull($component->get('dateFrom'));
        $this->assertNull($component->get('dateTo'));
    }

    public function test_dashboard_filters_collections_by_selected_date_preset(): void
    {
        $term1 = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Today Installment',
            'amount' => 50000.00,
            'status' => 'Pending',
            'due_date' => now()->toDateString(),
        ]);

        $term2 = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 2,
            'label' => 'Past Installment',
            'amount' => 50000.00,
            'status' => 'Pending',
            'due_date' => now()->subDays(15)->toDateString(),
        ]);

        // Payment 1 received Today: 20,000
        Payment::create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'term_id' => $term1->id,
            'code' => 'RCP-FILTER-01',
            'amount' => 20000.00,
            'tds' => 0.00,
            'mode' => 'UPI',
            'paid_on' => now()->toDateString(),
            'created_by' => $this->admin->id,
        ]);

        // Payment 2 received 15 days ago: 30,000
        Payment::create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'term_id' => $term2->id,
            'code' => 'RCP-FILTER-02',
            'amount' => 30000.00,
            'tds' => 0.00,
            'mode' => 'NEFT',
            'paid_on' => now()->subDays(15)->toDateString(),
            'created_by' => $this->admin->id,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(DashboardIndex::class);

        // Default 'today': only payment 1 (20,000) is counted in received
        $m = $component->viewData('m');
        $this->assertEquals(20000.00, $m['received']);

        // Switch to 'last_7_days': payment 2 (15 days ago) is not included
        $component->call('selectPreset', 'last_7_days');
        $m = $component->viewData('m');
        $this->assertEquals(20000.00, $m['received']);

        // Switch to 'last_30_days': both payments are included (50,000)
        $component->call('selectPreset', 'last_30_days');
        $m = $component->viewData('m');
        $this->assertEquals(50000.00, $m['received']);

        // Switch to 'all_time': both payments are included (50,000)
        $component->call('selectPreset', 'all_time');
        $m = $component->viewData('m');
        $this->assertEquals(50000.00, $m['received']);
    }

    public function test_dashboard_custom_date_range_selection(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(DashboardIndex::class)
            ->call('applyCustomRange', '2026-05-01', '2026-05-15');

        $this->assertEquals('custom', $component->get('datePreset'));
        $this->assertEquals('2026-05-01', $component->get('dateFrom'));
        $this->assertEquals('2026-05-15', $component->get('dateTo'));

        // Test inverted range auto-correction
        $component->call('applyCustomRange', '2026-06-30', '2026-06-01');
        $this->assertEquals('2026-06-01', $component->get('dateFrom'));
        $this->assertEquals('2026-06-30', $component->get('dateTo'));
    }

    public function test_dashboard_renders_all_preset_labels_and_calendar_markup(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(DashboardIndex::class);

        // Quick Picks
        $component->assertSee('Quick Picks')
            ->assertSee('Today')
            ->assertSee('Yesterday')
            ->assertSee('Last 7 Days')
            ->assertSee('Last 30 Days');

        // Calendar Periods
        $component->assertSee('Calendar Periods')
            ->assertSee('This Week')
            ->assertSee('Last Week')
            ->assertSee('This Month')
            ->assertSee('Last Month')
            ->assertSee('This Quarter')
            ->assertSee('Last Quarter')
            ->assertSee('Year to Date (YTD)')
            ->assertSee('Last Year');

        // Everything
        $component->assertSee('Everything')
            ->assertSee('All Time')
            ->assertSee('Custom Range...');
    }

    public function test_dashboard_due_metric_reacts_dynamically_to_date_preset(): void
    {
        // Term 1: Due today, 50,000 amount, 20,000 received -> 30,000 due
        $term1 = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Today Milestone',
            'amount' => 50000.00,
            'received' => 20000.00,
            'tds' => 0.00,
            'status' => 'Partial',
            'due_date' => now()->toDateString(),
        ]);

        // Term 2: Due 15 days ago, 50,000 amount, 30,000 received -> 20,000 due
        $term2 = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 2,
            'label' => 'Past Milestone',
            'amount' => 50000.00,
            'received' => 30000.00,
            'tds' => 0.00,
            'status' => 'Partial',
            'due_date' => now()->subDays(15)->toDateString(),
        ]);

        // Term 3: Due 20 days in future, 40,000 amount, 0 received -> 40,000 due
        $term3 = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 3,
            'label' => 'Future Milestone',
            'amount' => 40000.00,
            'received' => 0.00,
            'tds' => 0.00,
            'status' => 'Pending',
            'due_date' => now()->addDays(20)->toDateString(),
        ]);

        // Cancelled Term: Due today, 60,000 amount, 0 received -> MUST be omitted
        $termCancelled = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 4,
            'label' => 'Cancelled Milestone',
            'amount' => 60000.00,
            'received' => 0.00,
            'tds' => 0.00,
            'status' => 'Cancelled',
            'due_date' => now()->toDateString(),
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(DashboardIndex::class);

        // 1. Today: Only Term 1 is due today (30,000). Cancelled term (60,000) is omitted.
        $m = $component->viewData('m');
        $this->assertEquals(30000.00, $m['due']);

        // 2. Yesterday: Neither Term 1, Term 2 nor Term 3 is due yesterday -> 0
        $component->call('selectPreset', 'yesterday');
        $m = $component->viewData('m');
        $this->assertEquals(0.00, $m['due']);

        // 3. Last 7 Days: Only Term 1 is within window -> 30,000
        $component->call('selectPreset', 'last_7_days');
        $m = $component->viewData('m');
        $this->assertEquals(30000.00, $m['due']);

        // 4. Last 30 Days: Term 1 (30,000) + Term 2 (20,000) = 50,000. Term 3 (future) and Cancelled omitted.
        $component->call('selectPreset', 'last_30_days');
        $m = $component->viewData('m');
        $this->assertEquals(50000.00, $m['due']);

        // 5. All Time: Term 1 (30k) + Term 2 (20k) + Term 3 (40k) = 90,000. Cancelled strictly omitted.
        $component->call('selectPreset', 'all_time');
        $m = $component->viewData('m');
        $this->assertEquals(90000.00, $m['due']);
    }

    public function test_sales_dashboard_due_amount_reacts_dynamically_and_omits_cancelled_terms(): void
    {
        $salesUser = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Active',
            'must_change' => false,
        ]);

        $salesProject = Project::create([
            'project_code' => 'PJT-SALES-DUE-1',
            'customer_id' => $this->customer->id,
            'sales_person_id' => $salesUser->id,
            'name' => 'Enterprise CRM Rollout',
            'value' => 150000.00,
            'status' => 'In Progress',
            'health' => 'On Track',
        ]);

        // Term 1: Due Today, 50,000, 10,000 received -> 40,000 due
        PaymentTerm::create([
            'project_id' => $salesProject->id,
            'seq' => 1,
            'label' => 'Sales Milestone 1',
            'amount' => 50000.00,
            'received' => 10000.00,
            'tds' => 0.00,
            'status' => 'Partial',
            'due_date' => now()->toDateString(),
        ]);

        // Term 2: Due 10 days ago, 50,000, 0 received -> 50,000 due
        PaymentTerm::create([
            'project_id' => $salesProject->id,
            'seq' => 2,
            'label' => 'Sales Milestone 2',
            'amount' => 50000.00,
            'received' => 0.00,
            'tds' => 0.00,
            'status' => 'Overdue',
            'due_date' => now()->subDays(10)->toDateString(),
        ]);

        // Cancelled Term: Due today, 50,000 -> MUST be omitted
        PaymentTerm::create([
            'project_id' => $salesProject->id,
            'seq' => 3,
            'label' => 'Cancelled Sales Milestone',
            'amount' => 50000.00,
            'received' => 0.00,
            'tds' => 0.00,
            'status' => 'Cancelled',
            'due_date' => now()->toDateString(),
        ]);

        $component = Livewire::actingAs($salesUser)
            ->test(DashboardIndex::class);

        // Today: Only Term 1 is due today -> 40,000 (cancelled omitted)
        $salesData = $component->viewData('salesData');
        $this->assertEquals(40000.00, $salesData['dueAmount']);

        // Yesterday: 0 due
        $component->call('selectPreset', 'yesterday');
        $salesData = $component->viewData('salesData');
        $this->assertEquals(0.00, $salesData['dueAmount']);

        // Last 30 Days: Term 1 (40k) + Term 2 (50k) = 90,000 (cancelled omitted)
        $component->call('selectPreset', 'last_30_days');
        $salesData = $component->viewData('salesData');
        $this->assertEquals(90000.00, $salesData['dueAmount']);

        // All Time: 90,000 (cancelled omitted)
        $component->call('selectPreset', 'all_time');
        $salesData = $component->viewData('salesData');
        $this->assertEquals(90000.00, $salesData['dueAmount']);
    }
}
