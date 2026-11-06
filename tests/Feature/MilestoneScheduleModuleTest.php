<?php

namespace Tests\Feature;

use App\Livewire\Projects\ProjectMilestoneModal;
use App\Livewire\Projects\ProjectRecurringModal;
use App\Livewire\Projects\ProjectScheduleModal;
use App\Livewire\Schedule\ScheduleIndex;
use App\Models\Customer;
use App\Models\Milestone;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use App\Services\AuditNotificationService;
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

    public function test_milestone_is_locked_when_connected_to_payment_schedule(): void
    {
        $milestoneA = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Architecture Sign-off',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'Pending',
            'sort_order' => 1,
        ]);

        $milestoneB = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Prototype Demo',
            'due_date' => now()->addDays(20)->toDateString(),
            'status' => 'Pending',
            'sort_order' => 2,
        ]);

        PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Design Installment',
            'amount' => 25000.00,
            'trigger_type' => 'milestone',
            'milestone_id' => $milestoneA->id,
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'Pending',
            'received' => 0.00,
            'tds' => 0.00,
        ]);

        $this->assertTrue($milestoneA->isLocked());
        $this->assertFalse($milestoneB->isLocked());

        $component = Livewire::actingAs($this->admin)
            ->test(ProjectMilestoneModal::class)
            ->call('load', $this->project->id);

        $items = $component->get('items');
        $this->assertCount(2, $items);
        $this->assertTrue($items[0]['locked']);
        $this->assertStringContainsString('Design Installment', $items[0]['lockedReason']);
        $this->assertFalse($items[1]['locked']);
        $this->assertNull($items[1]['lockedReason']);

        $component->assertSee('Locked')
            ->assertSee('(Locked to Schedule)');
    }

    public function test_cannot_manually_remove_locked_milestone(): void
    {
        $milestone = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Backend Deployment',
            'due_date' => now()->addDays(15)->toDateString(),
            'status' => 'Pending',
            'sort_order' => 1,
        ]);

        PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Deployment Milestone Payment',
            'amount' => 50000.00,
            'trigger_type' => 'milestone',
            'milestone_id' => $milestone->id,
            'due_date' => now()->addDays(15)->toDateString(),
            'status' => 'Pending',
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(ProjectMilestoneModal::class)
            ->call('load', $this->project->id)
            ->call('removeRow', 0)
            ->assertHasErrors('items.0.locked')
            ->assertDispatched('toast');

        $items = $component->get('items');
        $this->assertCount(1, $items);
        $this->assertEquals($milestone->id, $items[0]['id']);

        $this->assertNotNull(Milestone::find($milestone->id));
    }

    public function test_loading_standard_template_preserves_locked_milestones(): void
    {
        $lockedMilestone = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Custom API Integration',
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'In Progress',
            'sort_order' => 1,
        ]);

        $unlockedMilestone = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Old Draft Task',
            'due_date' => now()->addDays(20)->toDateString(),
            'status' => 'Pending',
            'sort_order' => 2,
        ]);

        PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'API Milestone Payment',
            'amount' => 30000.00,
            'trigger_type' => 'milestone',
            'milestone_id' => $lockedMilestone->id,
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'Pending',
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(ProjectMilestoneModal::class)
            ->call('load', $this->project->id)
            ->call('loadStandardTemplate', 'website-development')
            ->assertDispatched('toast');

        $items = $component->get('items');

        // Verify locked milestone is retained
        $firstItem = $items[0];
        $this->assertEquals($lockedMilestone->id, $firstItem['id']);
        $this->assertEquals('Custom API Integration', $firstItem['name']);
        $this->assertTrue($firstItem['locked']);

        // Verify standard template stages were appended/merged
        $names = array_column($items, 'name');
        $this->assertContains('Kickoff & requirement gathering', $names);
        $this->assertContains('Wireframe & design approval', $names);
        $this->assertNotContains('Old Draft Task', $names);

        // Save and verify database integrity
        $component->call('save')->assertHasNoErrors();

        $this->assertNotNull(Milestone::find($lockedMilestone->id));
        $this->assertNull(Milestone::find($unlockedMilestone->id));

        $dbMilestones = Milestone::where('project_id', $this->project->id)->get();
        $this->assertTrue($dbMilestones->contains('id', $lockedMilestone->id));
    }

    public function test_saving_milestones_never_deletes_milestone_connected_to_payment_schedule(): void
    {
        $lockedMilestone = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Database Schema Approval',
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'Pending',
            'sort_order' => 1,
        ]);

        PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Schema Milestone Payment',
            'amount' => 20000.00,
            'trigger_type' => 'milestone',
            'milestone_id' => $lockedMilestone->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'Pending',
        ]);

        // Simulate a scenario where items list only has a newly added milestone
        $component = Livewire::actingAs($this->admin)
            ->test(ProjectMilestoneModal::class)
            ->call('load', $this->project->id)
            ->set('items', [
                [
                    'id' => null,
                    'name' => 'Brand New Milestone',
                    'dueDate' => now()->addDays(30)->toDateString(),
                    'status' => 'Pending',
                    'done' => false,
                    'done_on' => null,
                    'locked' => false,
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        // The locked milestone must NOT be deleted from the database
        $this->assertNotNull(Milestone::find($lockedMilestone->id));
    }

    public function test_schedule_modal_displays_received_and_due_amounts_for_locked_installments(): void
    {
        $term = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Phase 1 Advance',
            'amount' => 50000.00,
            'trigger_type' => 'fixed',
            'due_date' => now()->toDateString(),
            'status' => 'Partial',
            'received' => 20000.00,
            'tds' => 0.00,
            'grace_days' => 5,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id);

        $terms = $component->get('terms');
        $this->assertCount(1, $terms);
        $this->assertTrue($terms[0]['locked']);
        $this->assertEquals(20000.00, $terms[0]['received']);
        $this->assertEquals(30000.00, $terms[0]['due']);
        $this->assertEquals('Partial', $terms[0]['status']);

        $component->assertSee('Locked Installment')
            ->assertSee('Received:')
            ->assertSee('Pending Due:');
    }

    public function test_milestone_modal_displays_connected_scheduled_payment_details_and_amount(): void
    {
        $milestone = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Architecture Sign-off',
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'Pending',
            'sort_order' => 1,
        ]);

        PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Architecture Payment',
            'amount' => 45000.00,
            'trigger_type' => 'milestone',
            'milestone_id' => $milestone->id,
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'Paid',
            'received' => 45000.00,
            'tds' => 0.00,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(ProjectMilestoneModal::class)
            ->call('load', $this->project->id);

        $items = $component->get('items');
        $this->assertCount(1, $items);
        $this->assertTrue($items[0]['locked']);
        $this->assertNotEmpty($items[0]['connectedTerms']);
        $this->assertEquals(45000.00, $items[0]['connectedTerms'][0]['amount']);
        $this->assertEquals(45000.00, $items[0]['connectedTerms'][0]['received']);
        $this->assertEquals(0.00, $items[0]['connectedTerms'][0]['due']);
        $this->assertEquals('Paid', $items[0]['connectedTerms'][0]['status']);

        $component->assertSee('Connected Scheduled Payment')
            ->assertSee('Architecture Payment')
            ->assertSee('Paid');
    }

    public function test_split5050_preserves_recurring_payments_intact_and_does_not_count_them_in_remaining_balance(): void
    {
        PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Old Advance',
            'amount' => 50000.00,
            'trigger_type' => 'fixed',
            'due_date' => now()->toDateString(),
            'status' => 'Pending',
            'received' => 0.00,
            'tds' => 0.00,
        ]);

        $recurringTerm = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 2,
            'label' => 'Monthly Retainer - Oct',
            'amount' => 10000.00,
            'trigger_type' => 'recurring',
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

        $this->assertCount(3, $terms);
        $this->assertEquals('Advance', $terms[0]['label']);
        $this->assertEquals(50000.00, $terms[0]['amount']);
        $this->assertEquals('fixed', $terms[0]['trigger']);

        $this->assertEquals('On delivery', $terms[1]['label']);
        $this->assertEquals(50000.00, $terms[1]['amount']);

        $this->assertEquals($recurringTerm->id, $terms[2]['id']);
        $this->assertEquals('Monthly Retainer - Oct', $terms[2]['label']);
        $this->assertEquals(10000.00, $terms[2]['amount']);
        $this->assertEquals('recurring', $terms[2]['trigger']);

        $component->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('payment_terms', [
            'project_id' => $this->project->id,
            'label' => 'Advance',
            'amount' => 50000.00,
        ]);
        $this->assertDatabaseHas('payment_terms', [
            'project_id' => $this->project->id,
            'label' => 'On delivery',
            'amount' => 50000.00,
        ]);
        $this->assertDatabaseHas('payment_terms', [
            'project_id' => $this->project->id,
            'id' => $recurringTerm->id,
            'label' => 'Monthly Retainer - Oct',
            'amount' => 10000.00,
            'trigger_type' => 'recurring',
        ]);
    }

    public function test_autosplit_preserves_recurring_payments_intact_with_locked_payment(): void
    {
        $lockedTerm = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Deposit Paid',
            'amount' => 20000.00,
            'trigger_type' => 'fixed',
            'due_date' => now()->toDateString(),
            'status' => 'Paid',
            'received' => 20000.00,
            'tds' => 0.00,
        ]);

        PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 2,
            'label' => 'Milestone Placeholder',
            'amount' => 80000.00,
            'trigger_type' => 'fixed',
            'due_date' => now()->addDays(20)->toDateString(),
            'status' => 'Pending',
        ]);

        $recurringTerm = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 3,
            'label' => 'Monthly Maintenance',
            'amount' => 15000.00,
            'trigger_type' => 'recurring',
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'Pending',
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->call('autoSplit');

        $terms = $component->get('terms');

        $this->assertCount(5, $terms);
        $this->assertEquals($lockedTerm->id, $terms[0]['id']);
        $this->assertEquals(20000.00, $terms[0]['amount']);

        $this->assertEquals(24000.00, $terms[1]['amount']);
        $this->assertEquals(32000.00, $terms[2]['amount']);
        $this->assertEquals(24000.00, $terms[3]['amount']);

        $this->assertEquals($recurringTerm->id, $terms[4]['id']);
        $this->assertEquals(15000.00, $terms[4]['amount']);
        $this->assertEquals('recurring', $terms[4]['trigger']);
    }

    public function test_schedule_modal_renders_collapsible_sections_and_add_recurring_row(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->assertSee('Non-Recurring Installments')
            ->assertSee('Recurring Payments')
            ->call('addRecurringRow');

        $terms = $component->get('terms');
        $lastTerm = end($terms);

        $this->assertEquals('recurring', $lastTerm['trigger']);
        $this->assertStringContainsString('Recurring Retainer', $lastTerm['label']);
    }

    public function test_schedule_modal_save_is_atomic_and_rolls_back_on_failure(): void
    {
        $mockAudit = $this->mock(AuditNotificationService::class);
        $mockAudit->shouldReceive('log')->andReturnNull();
        $mockAudit->shouldReceive('notifyProjectStakeholders')->andThrow(new \RuntimeException('Transaction atomic failure simulation'));

        $initialTermCount = PaymentTerm::where('project_id', $this->project->id)->count();

        try {
            Livewire::actingAs($this->admin)
                ->test(ProjectScheduleModal::class)
                ->call('load', $this->project->id)
                ->set('terms', [
                    [
                        'id' => null,
                        'label' => 'New Term 1',
                        'amount' => 50000.00,
                        'percent' => 50,
                        'trigger' => 'fixed',
                        'milestoneId' => null,
                        'dueDate' => now()->addDays(10)->toDateString(),
                        'graceDays' => 0,
                        'locked' => false,
                        'received' => 0,
                        'tds' => 0,
                        'due' => 50000.00,
                        'status' => 'Pending',
                    ],
                    [
                        'id' => null,
                        'label' => 'New Term 2',
                        'amount' => 50000.00,
                        'percent' => 50,
                        'trigger' => 'fixed',
                        'milestoneId' => null,
                        'dueDate' => now()->addDays(20)->toDateString(),
                        'graceDays' => 0,
                        'locked' => false,
                        'received' => 0,
                        'tds' => 0,
                        'due' => 50000.00,
                        'status' => 'Pending',
                    ],
                ])
                ->call('save');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Transaction atomic failure simulation', $e->getMessage());
        }

        $this->assertEquals($initialTermCount, PaymentTerm::where('project_id', $this->project->id)->count());
    }

    public function test_recurring_modal_generate_is_atomic_and_rolls_back_on_failure(): void
    {
        $mockAudit = $this->mock(AuditNotificationService::class);
        $mockAudit->shouldReceive('log')->andThrow(new \RuntimeException('Recurring audit failure simulation'));

        $initialTermCount = PaymentTerm::where('project_id', $this->project->id)->count();

        try {
            Livewire::actingAs($this->admin)
                ->test(ProjectRecurringModal::class)
                ->call('load', $this->project->id)
                ->set('amount', 5000.00)
                ->set('months', 6)
                ->set('label', 'Retainer Month')
                ->call('generate');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Recurring audit failure simulation', $e->getMessage());
        }

        $this->assertEquals($initialTermCount, PaymentTerm::where('project_id', $this->project->id)->count());
    }
}
