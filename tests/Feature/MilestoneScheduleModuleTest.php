<?php

namespace Tests\Feature;

use App\Livewire\Projects\ProjectMilestoneModal;
use App\Livewire\Projects\ProjectScheduleModal;
use App\Livewire\Schedule\ScheduleIndex;
use App\Models\Customer;
use App\Models\Milestone;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MilestoneScheduleModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $coordinator;

    protected User $sales;

    protected Customer $customer;

    protected Project $project;

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
            'customer_code' => 'CUS-SCHED-1',
            'contact_name' => 'Acme Corporation',
            'phone' => '9876543210',
            'state' => 'MH',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $this->project = Project::create([
            'project_code' => 'PJT-SCHED-1',
            'customer_id' => $this->customer->id,
            'name' => 'Enterprise Portal',
            'value' => 100000.00,
            'cost' => 30000.00,
            'status' => 'In Progress',
            'health' => 'On Track',
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
            'start_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(60)->toDateString(),
        ]);
    }

    public function test_schedule_index_is_accessible_to_authorized_roles_and_forbidden_to_sales(): void
    {
        $this->actingAs($this->admin)->get('/schedules')->assertStatus(200);
        $this->actingAs($this->manager)->get('/schedules')->assertStatus(200);
        $this->actingAs($this->coordinator)->get('/schedules')->assertStatus(200);

        // Sales is restricted from viewing payment schedules
        $this->actingAs($this->sales)->get('/schedules')->assertStatus(403);
    }

    public function test_sidebar_renders_payment_schedule_for_authorized_roles_and_hides_for_sales(): void
    {
        $this->actingAs($this->admin)->get('/dashboard')
            ->assertSee('/schedules')
            ->assertSee('Payment Schedule');

        $this->actingAs($this->sales)->get('/dashboard')
            ->assertDontSee('/schedules');
    }

    public function test_project_schedule_modal_loads_project_context_and_available_milestones(): void
    {
        $m1 = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Architecture Sign-off',
            'sort_order' => 1,
            'due_date' => now()->addDays(15)->toDateString(),
            'status' => 'Pending',
            'done' => false,
        ]);

        $m2 = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Final Deployment',
            'sort_order' => 2,
            'due_date' => now()->addDays(60)->toDateString(),
            'status' => 'Pending',
            'done' => false,
        ]);

        Livewire::actingAs($this->coordinator)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->assertSet('projectId', $this->project->id)
            ->assertSet('projectValue', 100000.00)
            ->assertCount('availableMilestones', 2)
            ->set('terms.0.trigger', 'milestone')
            ->assertSee('Architecture Sign-off')
            ->assertSee('Final Deployment');
    }

    public function test_auto_split_30_40_30_balances_pennies_accurately(): void
    {
        $m1 = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Mid Milestone',
            'sort_order' => 1,
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'Pending',
            'done' => false,
        ]);

        $m2 = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Delivery Milestone',
            'sort_order' => 2,
            'due_date' => now()->addDays(60)->toDateString(),
            'status' => 'Pending',
            'done' => false,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->call('autoSplit');

        $terms = $component->get('terms');

        $this->assertCount(3, $terms);
        $this->assertEquals('Advance', $terms[0]['label']);
        $this->assertEquals(30000.00, $terms[0]['amount']);
        $this->assertEquals('fixed', $terms[0]['trigger']);

        $this->assertEquals('Mid project', $terms[1]['label']);
        $this->assertEquals(40000.00, $terms[1]['amount']);
        $this->assertEquals('milestone', $terms[1]['trigger']);
        $this->assertEquals($m1->id, $terms[1]['milestoneId']);

        $this->assertEquals('On delivery', $terms[2]['label']);
        $this->assertEquals(30000.00, $terms[2]['amount']);
        $this->assertEquals('milestone', $terms[2]['trigger']);
        $this->assertEquals($m2->id, $terms[2]['milestoneId']);

        $total = array_sum(array_column($terms, 'amount'));
        $this->assertEquals(100000.00, $total);
    }

    public function test_50_50_split_calculates_and_balances_terms(): void
    {
        $m1 = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Final Milestone',
            'sort_order' => 1,
            'due_date' => now()->addDays(45)->toDateString(),
            'status' => 'Pending',
            'done' => false,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->call('split5050');

        $terms = $component->get('terms');

        $this->assertCount(2, $terms);
        $this->assertEquals('Advance', $terms[0]['label']);
        $this->assertEquals(50000.00, $terms[0]['amount']);
        $this->assertEquals('fixed', $terms[0]['trigger']);

        $this->assertEquals('On delivery', $terms[1]['label']);
        $this->assertEquals(50000.00, $terms[1]['amount']);
        $this->assertEquals('milestone', $terms[1]['trigger']);
        $this->assertEquals($m1->id, $terms[1]['milestoneId']);

        $total = array_sum(array_column($terms, 'amount'));
        $this->assertEquals(100000.00, $total);
    }

    public function test_penny_matching_balance_checks_against_project_value(): void
    {
        // 1. Installment sum short of contract value displays unscheduled balance badge but allows saving
        Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->set('terms', [
                [
                    'id' => null,
                    'label' => 'Advance',
                    'amount' => 50000.00,
                    'trigger' => 'fixed',
                    'milestoneId' => null,
                    'dueDate' => now()->toDateString(),
                    'graceDays' => 0,
                    'locked' => false,
                ],
            ])
            ->assertSee('Unscheduled Balance')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payment_terms', [
            'project_id' => $this->project->id,
            'label' => 'Advance',
            'amount' => 50000.00,
        ]);

        // 2. Extra / recurring overage displays overage badge and allows saving
        Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->set('terms', [
                [
                    'id' => null,
                    'label' => 'Advance',
                    'amount' => 60000.00,
                    'trigger' => 'fixed',
                    'milestoneId' => null,
                    'dueDate' => now()->toDateString(),
                    'graceDays' => 0,
                    'locked' => false,
                ],
                [
                    'id' => null,
                    'label' => 'Extra Retainer',
                    'amount' => 60000.00,
                    'trigger' => 'recurring',
                    'milestoneId' => null,
                    'dueDate' => now()->addDays(30)->toDateString(),
                    'graceDays' => 0,
                    'locked' => false,
                ],
            ])
            ->assertSee('Extra / Recurring Overage')
            ->call('save')
            ->assertHasNoErrors();

        // 3. Installment sum accurately matches contract value displays matched badge
        Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->set('terms', [
                [
                    'id' => null,
                    'label' => 'Advance (50%)',
                    'amount' => 50000.00,
                    'trigger' => 'fixed',
                    'milestoneId' => null,
                    'dueDate' => now()->toDateString(),
                    'graceDays' => 0,
                    'locked' => false,
                ],
                [
                    'id' => null,
                    'label' => 'Final (50%)',
                    'amount' => 50000.00,
                    'trigger' => 'fixed',
                    'milestoneId' => null,
                    'dueDate' => now()->addDays(30)->toDateString(),
                    'graceDays' => 5,
                    'locked' => false,
                ],
            ])
            ->assertSee('Matched (100% scheduled)')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'project-schedule-modal');

        $this->assertDatabaseCount('payment_terms', 2);
        $this->assertDatabaseHas('payment_terms', [
            'project_id' => $this->project->id,
            'label' => 'Advance (50%)',
            'amount' => 50000.00,
        ]);
        $this->assertDatabaseHas('payment_terms', [
            'project_id' => $this->project->id,
            'label' => 'Final (50%)',
            'amount' => 50000.00,
        ]);
    }

    public function test_milestone_trigger_synchronizes_due_date_with_linked_milestone(): void
    {
        $milestone = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Beta Launch',
            'sort_order' => 1,
            'due_date' => '2026-11-20',
            'status' => 'Pending',
            'done' => false,
        ]);

        Livewire::actingAs($this->coordinator)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->set('terms', [
                [
                    'id' => null,
                    'label' => 'Advance',
                    'amount' => 40000.00,
                    'trigger' => 'fixed',
                    'milestoneId' => null,
                    'dueDate' => '2026-10-25',
                    'graceDays' => 0,
                    'locked' => false,
                ],
                [
                    'id' => null,
                    'label' => 'Beta Release Payment',
                    'amount' => 60000.00,
                    'trigger' => 'milestone',
                    'milestoneId' => $milestone->id,
                    'dueDate' => null,
                    'graceDays' => 7,
                    'locked' => false,
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $savedTerm = PaymentTerm::where('label', 'Beta Release Payment')->first();
        $this->assertNotNull($savedTerm);
        $this->assertEquals($milestone->id, $savedTerm->milestone_id);
        $this->assertEquals('2026-11-20', $savedTerm->due_date?->toDateString());
    }

    public function test_locked_terms_with_payments_cannot_be_deleted(): void
    {
        $term = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Initial Deposit',
            'amount' => 100000.00,
            'received' => 25000.00,
            'tds' => 0.00,
            'status' => 'Partial',
            'trigger_type' => 'fixed',
            'due_date' => now()->toDateString(),
            'grace_days' => 0,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->call('removeRow', 0)
            ->assertHasErrors(['terms.0.locked']);

        $this->assertDatabaseHas('payment_terms', ['id' => $term->id]);
    }

    public function test_schedule_index_displays_kpi_metrics_and_exports_csv(): void
    {
        PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Installment 1',
            'amount' => 60000.00,
            'received' => 60000.00,
            'tds' => 0.00,
            'status' => 'Paid',
            'trigger_type' => 'fixed',
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 2,
            'label' => 'Installment 2',
            'amount' => 40000.00,
            'received' => 0.00,
            'tds' => 0.00,
            'status' => 'Overdue',
            'trigger_type' => 'fixed',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(ScheduleIndex::class)
            ->assertSee('Complete Schedule')
            ->assertSee('Installment 1')
            ->assertSee('Installment 2')
            ->assertSee('Overdue')
            ->assertSee('Paid')
            ->call('exportCsv')
            ->assertFileDownloaded('payment-schedule-'.now()->toDateString().'.csv');
    }

    public function test_saving_milestones_converts_empty_date_strings_to_null(): void
    {
        $milestone = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Existing Milestone',
            'sort_order' => 1,
            'due_date' => '2026-10-25',
            'status' => 'Pending',
            'done' => false,
            'chase_notes' => null,
            'next_chase_date' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectMilestoneModal::class)
            ->call('load', $this->project->id)
            ->set('items.0.next_chase_date', '')
            ->set('items.0.chase_notes', '')
            ->set('items.0.dueDate', '')
            ->call('save')
            ->assertHasNoErrors();

        $milestone->refresh();
        $this->assertNull($milestone->next_chase_date);
        $this->assertNull($milestone->chase_notes);
        $this->assertNull($milestone->due_date);

        // Also verify direct Eloquent mutator converts empty string to null
        $milestone->update([
            'due_date' => '',
            'done_on' => '',
            'next_chase_date' => '',
            'chase_notes' => '',
        ]);

        $milestone->refresh();
        $this->assertNull($milestone->due_date);
        $this->assertNull($milestone->done_on);
        $this->assertNull($milestone->next_chase_date);
        $this->assertNull($milestone->chase_notes);
    }

    public function test_split5050_preserves_locked_payment_and_splits_remaining_balance(): void
    {
        // Project value = 100,000
        $lockedTerm = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Advance Deposit',
            'amount' => 30000.00,
            'trigger_type' => 'fixed',
            'due_date' => now()->toDateString(),
            'status' => 'Paid',
            'received' => 30000.00,
            'tds' => 0.00,
        ]);

        $unlockedTerm = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 2,
            'label' => 'Final Payment',
            'amount' => 70000.00,
            'trigger_type' => 'fixed',
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'Pending',
            'received' => 0.00,
            'tds' => 0.00,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->call('split5050');

        $terms = $component->get('terms');

        // Must have 3 terms: 1 locked + 2 new terms splitting the remaining 70,000 (50/50 = 35,000 + 35,000)
        $this->assertCount(3, $terms);
        $this->assertTrue($terms[0]['locked']);
        $this->assertEquals($lockedTerm->id, $terms[0]['id']);
        $this->assertEquals(30000.00, $terms[0]['amount']);
        $this->assertEquals('Advance Deposit', $terms[0]['label']);

        $this->assertFalse($terms[1]['locked']);
        $this->assertEquals(35000.00, $terms[1]['amount']);
        $this->assertEquals('Interim Installment', $terms[1]['label']);

        $this->assertFalse($terms[2]['locked']);
        $this->assertEquals(35000.00, $terms[2]['amount']);
        $this->assertEquals('On delivery', $terms[2]['label']);

        $this->assertEquals(100000.00, array_sum(array_column($terms, 'amount')));

        // Save and verify database integrity
        $component->call('save')->assertHasNoErrors();

        $dbTerms = PaymentTerm::where('project_id', $this->project->id)->orderBy('seq')->get();
        $this->assertCount(3, $dbTerms);
        $this->assertEquals($lockedTerm->id, $dbTerms[0]->id);
        $this->assertEquals(30000.00, (float) $dbTerms[0]->amount);
        $this->assertEquals(30000.00, (float) $dbTerms[0]->received);
        $this->assertEquals('Paid', $dbTerms[0]->status);

        $this->assertEquals(35000.00, (float) $dbTerms[1]->amount);
        $this->assertEquals(35000.00, (float) $dbTerms[2]->amount);
    }

    public function test_autosplit_30_40_30_preserves_locked_payment_and_splits_remaining_balance(): void
    {
        // Project value = 100,000
        $lockedTerm = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Initial Milestone Payment',
            'amount' => 40000.00,
            'trigger_type' => 'fixed',
            'due_date' => now()->toDateString(),
            'status' => 'Paid',
            'received' => 40000.00,
            'tds' => 0.00,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->call('autoSplit');

        $terms = $component->get('terms');

        // Remaining = 60,000. Split 30% (18,000) / 40% (24,000) / 30% (18,000)
        $this->assertCount(4, $terms);
        $this->assertTrue($terms[0]['locked']);
        $this->assertEquals($lockedTerm->id, $terms[0]['id']);
        $this->assertEquals(40000.00, $terms[0]['amount']);

        $this->assertFalse($terms[1]['locked']);
        $this->assertEquals(18000.00, $terms[1]['amount']);

        $this->assertFalse($terms[2]['locked']);
        $this->assertEquals(24000.00, $terms[2]['amount']);

        $this->assertFalse($terms[3]['locked']);
        $this->assertEquals(18000.00, $terms[3]['amount']);

        $this->assertEquals(100000.00, array_sum(array_column($terms, 'amount')));

        $component->call('save')->assertHasNoErrors();

        $dbTerms = PaymentTerm::where('project_id', $this->project->id)->orderBy('seq')->get();
        $this->assertCount(4, $dbTerms);
        $this->assertEquals($lockedTerm->id, $dbTerms[0]->id);
        $this->assertEquals(40000.00, (float) $dbTerms[0]->amount);
        $this->assertEquals('Paid', $dbTerms[0]->status);
    }

    public function test_split_when_locked_terms_cover_project_value_dispatches_warning(): void
    {
        $lockedTerm = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Full Payment',
            'amount' => 100000.00,
            'trigger_type' => 'fixed',
            'due_date' => now()->toDateString(),
            'status' => 'Paid',
            'received' => 100000.00,
            'tds' => 0.00,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->call('split5050')
            ->assertDispatched('toast');

        $terms = $component->get('terms');
        $this->assertCount(1, $terms);
        $this->assertTrue($terms[0]['locked']);
        $this->assertEquals(100000.00, $terms[0]['amount']);
    }
}
