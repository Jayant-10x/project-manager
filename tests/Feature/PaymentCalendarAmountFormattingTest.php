<?php

namespace Tests\Feature;

use App\Livewire\Calendar\PaymentCalendarIndex;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentCalendarAmountFormattingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Customer $customer;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-cal@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'Admin',
            'status' => 'Active',
            'must_change' => false,
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUS-CAL-FMT',
            'company' => 'Acme Cal Format Inc',
            'contact_name' => 'Cal User',
            'phone' => '9876543210',
            'state' => 'MH',
            'status' => 'Active',
            'approval_status' => 'Approved',
            'created_by' => $this->admin->id,
        ]);

        $this->project = Project::create([
            'project_code' => 'PJT-CAL-FMT',
            'name' => 'Calendar Amount Format Project',
            'customer_id' => $this->customer->id,
            'status' => 'In Progress',
            'approval_status' => 'Approved',
            'coordinator_id' => $this->admin->id,
            'value' => 100000.00,
            'cost' => 30000.00,
            'start_date' => now()->startOfMonth()->toDateString(),
        ]);
    }

    public function test_calendar_view_displays_full_amount_in_day_grid(): void
    {
        $today = Carbon::today();

        // 1. Create a scheduled term of ₹25,000 due today
        PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Sprint 1 Payment',
            'amount' => 25000.00,
            'received' => 0.00,
            'tds' => 0.00,
            'status' => 'Pending',
            'trigger_type' => 'fixed',
            'due_date' => $today->toDateString(),
            'grace_days' => 0,
        ]);

        // 2. Create a payment receipt of ₹25,000 received today
        Payment::create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'code' => 'RCP-CAL-25K',
            'amount' => 25000.00,
            'tds' => 0.00,
            'mode' => 'NEFT',
            'paid_on' => $today->toDateString(),
            'recorded_by' => $this->admin->id,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(PaymentCalendarIndex::class)
            ->set('month', $today->format('Y-m'));

        // The day grid must render amount: 25000 (and NOT truncated to amount: 25)
        $component->assertSeeHtml('amount: 25000');
        $component->assertDontSeeHtml('amount: 25 }');

        // The initial server-side fallback text must be ₹25,000 (and NOT ₹25)
        $component->assertSeeHtml('₹25,000');
    }

    public function test_money_component_safely_handles_comma_formatted_strings(): void
    {
        // 1. Passing a string formatted with comma
        $htmlFromString = Blade::render('<x-money :value="\'25,000\'" />');
        $this->assertStringContainsString('amount: 25000', $htmlFromString);
        $this->assertStringContainsString('₹25,000', $htmlFromString);
        $this->assertStringNotContainsString('amount: 25 }', $htmlFromString);

        // 2. Passing a raw float
        $htmlFromFloat = Blade::render('<x-money :value="25000.00" />');
        $this->assertStringContainsString('amount: 25000', $htmlFromFloat);
        $this->assertStringContainsString('₹25,000', $htmlFromFloat);

        // 3. Passing a string with currency symbol and commas
        $htmlFromSymbol = Blade::render('<x-money :value="\'₹25,000.00\'" />');
        $this->assertStringContainsString('amount: 25000', $htmlFromSymbol);
        $this->assertStringContainsString('₹25,000', $htmlFromSymbol);
    }
}
