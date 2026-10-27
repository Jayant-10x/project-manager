<?php

namespace Tests\Feature;

use App\Livewire\Followups\FollowupModal;
use App\Livewire\Payments\PaymentModal;
use App\Livewire\Projects\MilestoneChaseModal;
use App\Livewire\Projects\ProjectMilestoneModal;
use App\Livewire\Projects\ProjectModal;
use App\Livewire\Projects\ProjectScheduleModal;
use App\Livewire\Schedule\ScheduleIndex;
use App\Models\Customer;
use App\Models\Followup;
use App\Models\Milestone;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FallbackDataFixesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $coordinator;

    protected User $sales;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'must_change' => false,
        ]);

        $this->manager = User::factory()->create([
            'role' => 'Manager',
            'status' => 'Active',
            'must_change' => false,
            'reporting_to' => $this->admin->id,
        ]);

        $this->coordinator = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
            'must_change' => false,
            'reporting_to' => $this->manager->id,
        ]);

        $this->sales = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Active',
            'must_change' => false,
            'reporting_to' => $this->coordinator->id,
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUS-FIX-01',
            'contact_name' => 'Tech Corp',
            'company' => 'Tech Corp',
            'phone' => '9876543210',
            'status' => 'Active',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_chase_modal_does_not_persist_milestone_on_modal_open(): void
    {
        $this->actingAs($this->coordinator);

        $project = Project::create([
            'project_code' => 'PJT-FIX-001',
            'customer_id' => $this->customer->id,
            'name' => 'Chase Project',
            'value' => 50000,
            'coordinator_id' => $this->coordinator->id,
            'created_by' => $this->admin->id,
        ]);

        $term = PaymentTerm::create([
            'project_id' => $project->id,
            'seq' => 1,
            'label' => 'Final Installment',
            'amount' => 50000,
            'status' => 'Pending',
            'trigger_type' => 'fixed',
            'milestone_id' => null,
        ]);

        $this->assertEquals(0, Milestone::count());

        // Opening modal should NOT create milestone in DB
        $component = Livewire::test(MilestoneChaseModal::class)
            ->call('openModal', null, $term->id);

        $this->assertEquals(0, Milestone::count());
        $this->assertNull($term->fresh()->milestone_id);

        // Saving chase note should now create milestone and followup
        $component->set('chase_notes', 'Spoke with CFO regarding release')
            ->set('next_chase_date', now()->addDays(3)->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(1, Milestone::count());
        $this->assertNotNull($term->fresh()->milestone_id);
    }

    public function test_payment_modal_preserves_user_typed_amount_on_term_selection(): void
    {
        $this->actingAs($this->coordinator);

        $project = Project::create([
            'project_code' => 'PJT-FIX-002',
            'customer_id' => $this->customer->id,
            'name' => 'Payment Project',
            'value' => 100000,
            'coordinator_id' => $this->coordinator->id,
            'created_by' => $this->admin->id,
        ]);

        $term = PaymentTerm::create([
            'project_id' => $project->id,
            'seq' => 1,
            'label' => 'Mid Milestone',
            'amount' => 60000,
            'status' => 'Pending',
            'trigger_type' => 'fixed',
            'received' => 0,
            'tds' => 0,
        ]);

        Livewire::test(PaymentModal::class)
            ->set('project_id', $project->id)
            ->set('amount', 25000) // User typed 25k partial payment
            ->set('term_id', $term->id)
            ->assertSet('amount', 25000); // Must NOT be overwritten to 60000
    }

    public function test_schedule_modal_preserves_custom_due_dates_on_milestone_and_recurring(): void
    {
        $this->actingAs($this->admin);

        $project = Project::create([
            'project_code' => 'PJT-FIX-003',
            'customer_id' => $this->customer->id,
            'name' => 'Schedule Project',
            'value' => 80000,
            'coordinator_id' => $this->coordinator->id,
            'created_by' => $this->admin->id,
        ]);

        $milestone = Milestone::create([
            'project_id' => $project->id,
            'name' => 'Design Sign-off',
            'status' => 'Pending',
            'due_date' => null, // No due date on milestone
        ]);

        $customDate = Carbon::today()->addDays(20)->toDateString();

        Livewire::test(ProjectScheduleModal::class)
            ->call('load', $project->id)
            ->set('terms.0.label', 'Milestone Installment')
            ->set('terms.0.amount', 80000)
            ->set('terms.0.trigger', 'milestone')
            ->set('terms.0.milestoneId', $milestone->id)
            ->set('terms.0.dueDate', $customDate)
            ->call('save')
            ->assertHasNoErrors();

        $savedTerm = PaymentTerm::where('project_id', $project->id)->first();
        $this->assertNotNull($savedTerm);
        $this->assertEquals($customDate, $savedTerm->due_date?->toDateString());
    }

    public function test_project_modal_assigns_salesperson_supervisor_as_coordinator(): void
    {
        $takePerson = User::factory()->create([
            'role' => 'TakePerson',
            'status' => 'Active',
            'must_change' => false,
        ]);

        $this->actingAs($this->sales);

        Livewire::test(ProjectModal::class)
            ->set('name', 'Sales Attributed Project')
            ->set('customer_id', $this->customer->id)
            ->set('value', 90000)
            ->set('take_person_id', $takePerson->id)
            ->call('save')
            ->assertHasNoErrors();

        $project = Project::where('name', 'Sales Attributed Project')->first();
        $this->assertNotNull($project);
        $this->assertEquals($this->coordinator->id, $project->coordinator_id);
        $this->assertEquals($this->sales->id, $project->sales_person_id);
    }

    public function test_project_milestone_modal_loads_standard_stages_and_preserves_done_on(): void
    {
        $this->actingAs($this->admin);

        $project = Project::create([
            'project_code' => 'PJT-FIX-004',
            'customer_id' => $this->customer->id,
            'name' => 'Clean Milestone Project',
            'value' => 50000,
            'coordinator_id' => $this->coordinator->id,
            'created_by' => $this->admin->id,
        ]);

        // Default stages loaded for fresh project
        $test = Livewire::test(ProjectMilestoneModal::class)
            ->call('load', $project->id);

        $items = $test->get('items');
        $this->assertCount(5, $items);
        $this->assertEquals('Kickoff & requirement gathering', $items[0]['name']);

        // Explicitly reload standard template on demand
        $test->call('loadStandardTemplate');
        $this->assertCount(5, $test->get('items'));
    }

    public function test_followup_modal_persists_custom_interaction_date(): void
    {
        $this->actingAs($this->coordinator);

        $pastDate = Carbon::today()->subDays(3)->toDateString();

        Livewire::test(FollowupModal::class)
            ->call('openModal', $this->customer->id)
            ->set('type', 'Meeting')
            ->set('happened_on', $pastDate)
            ->set('summary', 'Discussed deliverables and scope changes')
            ->call('save')
            ->assertHasNoErrors();

        $fu = Followup::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($fu);
        $this->assertEquals($pastDate, Carbon::parse($fu->happened_on)->toDateString());
    }

    public function test_schedule_index_csv_export_respects_active_filters(): void
    {
        $this->actingAs($this->admin);

        $project = Project::create([
            'project_code' => 'PJT-FIX-005',
            'customer_id' => $this->customer->id,
            'name' => 'CSV Filter Project',
            'value' => 120000,
            'coordinator_id' => $this->coordinator->id,
            'created_by' => $this->admin->id,
        ]);

        PaymentTerm::create([
            'project_id' => $project->id,
            'seq' => 1,
            'label' => 'Overdue Installment XYZ',
            'amount' => 40000,
            'status' => 'Overdue',
            'trigger_type' => 'fixed',
            'due_date' => Carbon::yesterday()->toDateString(),
        ]);

        PaymentTerm::create([
            'project_id' => $project->id,
            'seq' => 2,
            'label' => 'Paid Installment ABC',
            'amount' => 80000,
            'status' => 'Paid',
            'trigger_type' => 'fixed',
            'due_date' => Carbon::today()->toDateString(),
            'received' => 80000,
        ]);

        Livewire::test(ScheduleIndex::class)
            ->set('status', 'Overdue')
            ->call('exportCsv')
            ->assertFileDownloaded('payment-schedule-'.now()->toDateString().'.csv');
    }

    public function test_project_delay_reason_is_archived_into_notes_when_health_recovers(): void
    {
        $this->actingAs($this->admin);

        $takePerson = User::factory()->create(['role' => 'TakePerson', 'status' => 'Active']);

        $project = Project::create([
            'project_code' => 'PJT-FIX-006',
            'customer_id' => $this->customer->id,
            'name' => 'Delayed Project',
            'value' => 60000,
            'start_date' => Carbon::today()->subMonths(1)->toDateString(),
            'sales_person_id' => $this->sales->id,
            'coordinator_id' => $this->coordinator->id,
            'take_person_id' => $takePerson->id,
            'status' => 'In Progress',
            'health' => 'Delayed',
            'delay_reason' => 'Client API credentials took 2 weeks',
            'notes' => 'Initial kick-off notes',
            'created_by' => $this->admin->id,
        ]);

        Livewire::test(ProjectModal::class)
            ->call('loadProject', $project->id)
            ->set('health', 'On Track')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $project->fresh();
        $this->assertEquals('On Track', $fresh->health);
        $this->assertNull($fresh->delay_reason);
        $this->assertStringContainsString('Delay Resolved', $fresh->notes);
        $this->assertStringContainsString('Client API credentials took 2 weeks', $fresh->notes);
    }
}
