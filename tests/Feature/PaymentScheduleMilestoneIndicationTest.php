<?php

namespace Tests\Feature;

use App\Livewire\Calendar\PaymentCalendarIndex;
use App\Livewire\Customers\CustomerDetail;
use App\Livewire\Payments\PaymentIndex;
use App\Livewire\Payments\PaymentModal;
use App\Livewire\Projects\ProjectDetail;
use App\Livewire\Schedule\ScheduleIndex;
use App\Livewire\Today\TodayWorkIndex;
use App\Models\Customer;
use App\Models\Milestone;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentScheduleMilestoneIndicationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Customer $customer;

    protected Project $project;

    protected Milestone $milestone;

    protected PaymentTerm $termWithMilestone;

    protected PaymentTerm $termFixed;

    protected Payment $paymentForTerm;

    protected Payment $paymentOnAccount;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
        Storage::fake('public');

        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'name' => 'Admin Indication Tester',
            'email' => 'admin-indication@test.com',
            'must_change' => false,
        ]);

        $this->actingAs($this->admin);

        $this->customer = Customer::create([
            'customer_code' => 'CUS-LOG-01',
            'contact_name' => 'Global Logistics Inc',
            'company_name' => 'Global Logistics Inc',
            'email' => 'finance@globallogistics.com',
            'phone' => '9876543210',
            'status' => 'Active',
            'payment_status' => 'Pending',
        ]);

        $this->project = Project::create([
            'project_code' => 'PRJ-LOG-01',
            'customer_id' => $this->customer->id,
            'name' => 'Logistics ERP Suite',
            'code' => 'PRJ-LOG-01',
            'status' => 'In Progress',
            'contract_value' => 150000.00,
            'payment_type' => 'Milestone-based',
            'start_date' => Carbon::now()->subDays(10)->toDateString(),
            'end_date' => Carbon::now()->addDays(60)->toDateString(),
            'created_by' => $this->admin->id,
        ]);

        $this->milestone = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Warehouse Dispatch Module',
            'due_date' => Carbon::now()->addDays(14)->toDateString(),
            'sort_order' => 1,
            'done' => false,
        ]);

        $this->termWithMilestone = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Warehouse Module Signoff',
            'amount' => 50000.00,
            'percent' => 33.33,
            'trigger_type' => 'milestone',
            'milestone_id' => $this->milestone->id,
            'due_date' => Carbon::now()->addDays(14)->toDateString(),
            'grace_days' => 5,
            'status' => 'Partial',
            'received' => 20000.00,
            'tds' => 0.00,
        ]);

        $this->termFixed = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 2,
            'label' => 'Final Deployment & Training',
            'amount' => 100000.00,
            'percent' => 66.67,
            'trigger_type' => 'fixed',
            'milestone_id' => null,
            'due_date' => Carbon::now()->addDays(45)->toDateString(),
            'status' => 'Pending',
            'received' => 0.00,
            'tds' => 0.00,
        ]);

        $this->paymentForTerm = Payment::create([
            'code' => 'RCP-LOG-001',
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'term_id' => $this->termWithMilestone->id,
            'amount' => 20000.00,
            'tds' => 0.00,
            'paid_on' => Carbon::now()->subDays(2)->toDateString(),
            'mode' => 'NEFT',
            'reference' => 'UTR-LOG-20000',
            'bank' => 'HDFC Bank',
            'created_by' => $this->admin->id,
        ]);

        $this->paymentOnAccount = Payment::create([
            'code' => 'RCP-LOG-002',
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'term_id' => null,
            'amount' => 5000.00,
            'tds' => 0.00,
            'paid_on' => Carbon::now()->subDays(1)->toDateString(),
            'mode' => 'UPI',
            'reference' => 'UPI-ON-ACCT-5K',
            'bank' => 'ICICI Bank',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_project_detail_payments_tab_shows_installment_and_milestone_indication(): void
    {
        Livewire::test(ProjectDetail::class, ['project' => $this->project])
            ->set('activeTab', 'payments')
            ->assertSee('Installment / Milestone')
            ->assertSee('Warehouse Module Signoff')
            ->assertSee('Warehouse Dispatch Module')
            ->assertSee('Milestone')
            ->assertSee('On account / Advance');
    }

    public function test_project_detail_schedule_tab_shows_payment_type_and_milestone(): void
    {
        Livewire::test(ProjectDetail::class, ['project' => $this->project])
            ->set('activeTab', 'schedule')
            ->assertSee('Milestone')
            ->assertSee('Warehouse Dispatch Module')
            ->assertSee('Fixed');
    }

    public function test_customer_detail_payments_tab_shows_installment_and_milestone_in_receipts_and_schedule(): void
    {
        Livewire::test(CustomerDetail::class, ['customer' => $this->customer])
            ->set('activeTab', 'payments')
            ->assertSee('Warehouse Module Signoff')
            ->assertSee('Warehouse Dispatch Module')
            ->assertSee('Logistics ERP Suite')
            ->assertSee('Installment / Milestone')
            ->assertSee('On account / Advance');
    }

    public function test_payment_index_shows_installment_payment_type_and_milestone(): void
    {
        Livewire::test(PaymentIndex::class)
            ->assertSee('Logistics ERP Suite')
            ->assertSee('Warehouse Module Signoff')
            ->assertSee('Warehouse Dispatch Module')
            ->assertSee('On account');
    }

    public function test_schedule_index_shows_payment_type_and_milestone(): void
    {
        Livewire::test(ScheduleIndex::class)
            ->assertSee('Logistics ERP Suite')
            ->assertSee('Warehouse Module Signoff')
            ->assertSee('Warehouse Dispatch Module')
            ->assertSee('Milestone linked')
            ->assertSee('Fixed date');
    }

    public function test_payment_modal_options_display_payment_type_and_milestone(): void
    {
        Livewire::test(PaymentModal::class)
            ->call('openPaymentModal', $this->project->id)
            ->assertSee('Warehouse Module Signoff')
            ->assertSee('Milestone: Warehouse Dispatch Module');
    }

    public function test_payment_calendar_drawer_shows_milestone_and_payment_type_for_dues_and_receipts(): void
    {
        $dueDate = Carbon::parse($this->termWithMilestone->due_date)->toDateString();
        $paidDate = Carbon::parse($this->paymentForTerm->paid_on)->toDateString();

        Livewire::test(PaymentCalendarIndex::class)
            ->call('selectDate', $dueDate)
            ->assertSee('Warehouse Module Signoff')
            ->assertSee('Warehouse Dispatch Module');

        Livewire::test(PaymentCalendarIndex::class)
            ->call('selectDate', $paidDate)
            ->assertSee('RCP-LOG-001')
            ->assertSee('Warehouse Module Signoff')
            ->assertSee('Warehouse Dispatch Module');
    }

    public function test_today_work_shows_milestone_and_payment_type_for_scheduled_payments(): void
    {
        // Create an upcoming term due in 3 days with milestone
        $upcomingMilestone = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'API Integration Phase',
            'due_date' => Carbon::now()->addDays(3)->toDateString(),
            'sort_order' => 2,
            'done' => false,
        ]);

        PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 3,
            'label' => 'API Delivery Milestone Fee',
            'amount' => 30000.00,
            'percent' => 20.00,
            'trigger_type' => 'milestone',
            'milestone_id' => $upcomingMilestone->id,
            'due_date' => Carbon::now()->addDays(3)->toDateString(),
            'status' => 'Pending',
            'received' => 0.00,
            'tds' => 0.00,
        ]);

        Livewire::test(TodayWorkIndex::class)
            ->assertSee('API Delivery Milestone Fee')
            ->assertSee('Milestone: API Integration Phase')
            ->assertSee('Milestone');
    }
}
