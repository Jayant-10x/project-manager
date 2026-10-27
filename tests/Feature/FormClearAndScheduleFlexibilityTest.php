<?php

namespace Tests\Feature;

use App\Livewire\Payments\PaymentModal;
use App\Livewire\Projects\ProjectModal;
use App\Livewire\Projects\ProjectScheduleModal;
use App\Livewire\Settings\UserModal;
use App\Models\Customer;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormClearAndScheduleFlexibilityTest extends TestCase
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
            'customer_code' => 'CUST-001',
            'company_name' => 'Tech Corp',
            'contact_person' => 'John Doe',
            'email' => 'john@techcorp.test',
            'phone' => '9876543210',
            'status' => 'Active',
        ]);

        $this->project = Project::create([
            'project_code' => 'PJT-001',
            'name' => 'Custom ERP',
            'customer_id' => $this->customer->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 100000.00,
            'cost' => 40000.00,
            'start_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(60)->toDateString(),
        ]);
    }

    public function test_payment_modal_clearing_amount_and_tds_leaves_fields_empty(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PaymentModal::class)
            ->call('openPaymentModal', $this->project->id)
            ->assertSet('tds', null)
            ->set('amount', 5000.00)
            ->set('tds', 500.00)
            ->assertSet('amount', 5000.00)
            ->assertSet('tds', 500.00)
            ->set('amount', '')
            ->set('tds', '')
            ->assertSet('amount', '')
            ->assertSet('tds', '');
    }

    public function test_project_schedule_modal_clearing_amount_leaves_field_empty(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id);

        $component->set('terms.0.amount', 25000.00);
        $this->assertEquals(25000.00, $component->get('terms.0.amount'));

        // Clear the installment amount
        $component->set('terms.0.amount', '');
        $this->assertEquals('', $component->get('terms.0.amount'));
    }

    public function test_project_modal_clearing_cost_and_amc_leaves_fields_empty(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectModal::class)
            ->call('openModal', $this->project->id)
            ->assertSet('cost', 40000.00)
            ->set('cost', '')
            ->assertSet('cost', '')
            ->set('amc_amount', 12000.00)
            ->set('amc_amount', '')
            ->assertSet('amc_amount', '');
    }

    public function test_user_modal_clearing_target_leaves_field_empty(): void
    {
        $salesUser = User::factory()->create([
            'role' => 'Sales',
            'target' => 500000.00,
        ]);

        Livewire::actingAs($this->admin)
            ->test(UserModal::class)
            ->call('openModal', $salesUser->id)
            ->assertSet('target', 500000.00)
            ->set('target', '')
            ->assertSet('target', '');
    }

    public function test_payment_schedule_modal_50_50_split_divides_cleanly(): void
    {
        $project = Project::create([
            'project_code' => 'PJT-SPLIT',
            'name' => 'Split 5050 Project',
            'customer_id' => $this->customer->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 80000.00,
            'start_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(60)->toDateString(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $project->id)
            ->call('split5050')
            ->assertSee('50-50 Split')
            ->assertSee('Matched (100% scheduled)')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('payment_terms', 2);
        $this->assertDatabaseHas('payment_terms', [
            'project_id' => $project->id,
            'label' => 'Advance',
            'amount' => 40000.00,
        ]);
        $this->assertDatabaseHas('payment_terms', [
            'project_id' => $project->id,
            'label' => 'On delivery',
            'amount' => 40000.00,
        ]);
    }

    public function test_payment_schedule_allows_submitting_extra_or_recurring_payments_with_calculation(): void
    {
        $project = Project::create([
            'project_code' => 'PJT-OVERAGE',
            'name' => 'Overage Project',
            'customer_id' => $this->customer->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 100000.00,
            'start_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(60)->toDateString(),
        ]);

        // 1. Over-scheduled (extra payments/retainer)
        Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $project->id)
            ->set('terms', [
                [
                    'id' => null,
                    'label' => 'Advance Milestone',
                    'amount' => 100000.00,
                    'trigger' => 'fixed',
                    'dueDate' => now()->toDateString(),
                    'milestoneId' => null,
                    'graceDays' => 0,
                    'locked' => false,
                ],
                [
                    'id' => null,
                    'label' => 'Extra Feature Add-on',
                    'amount' => 25000.00,
                    'trigger' => 'fixed',
                    'dueDate' => now()->addDays(30)->toDateString(),
                    'milestoneId' => null,
                    'graceDays' => 0,
                    'locked' => false,
                ],
            ])
            ->assertSee('Extra / Recurring Overage')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(2, PaymentTerm::where('project_id', $project->id)->count());
        $this->assertEquals(125000.00, (float) PaymentTerm::where('project_id', $project->id)->sum('amount'));

        // 2. Under-scheduled (partial phases)
        $project2 = Project::create([
            'project_code' => 'PJT-UNDER',
            'name' => 'Under Scheduled Project',
            'customer_id' => $this->customer->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 100000.00,
            'start_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(60)->toDateString(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $project2->id)
            ->set('terms', [
                [
                    'id' => null,
                    'label' => 'Phase 1 Initial',
                    'amount' => 40000.00,
                    'trigger' => 'fixed',
                    'dueDate' => now()->toDateString(),
                    'milestoneId' => null,
                    'graceDays' => 0,
                    'locked' => false,
                ],
            ])
            ->assertSee('Unscheduled Balance')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(1, PaymentTerm::where('project_id', $project2->id)->count());
        $this->assertEquals(40000.00, (float) PaymentTerm::where('project_id', $project2->id)->sum('amount'));
    }
}
