<?php

namespace Tests\Feature;

use App\Livewire\Projects\ProjectMilestoneModal;
use App\Livewire\Projects\ProjectScheduleModal;
use App\Livewire\Settings\SettingIndex;
use App\Models\Customer;
use App\Models\Milestone;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CloneFormRetainInstanceTest extends TestCase
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
            'name' => 'Admin Tester',
            'email' => 'admin-clone@test.com',
            'must_change' => false,
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUS-CLN-1',
            'contact_name' => 'Acme Repeaters Corp',
            'phone' => '9876543210',
            'state' => 'MH',
            'status' => 'Active',
            'sales_person_id' => $this->admin->id,
            'created_by' => $this->admin->id,
        ]);

        $this->project = Project::create([
            'project_code' => 'PJT-CLN-1',
            'customer_id' => $this->customer->id,
            'name' => 'Clone Form Test Project',
            'value' => 75000.00,
            'cost' => 25000.00,
            'status' => 'In Progress',
            'health' => 'On Track',
            'coordinator_id' => $this->admin->id,
            'sales_person_id' => $this->admin->id,
            'start_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(45)->toDateString(),
        ]);
    }

    public function test_milestone_modal_hides_remove_button_when_single_instance(): void
    {
        Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Sole Milestone',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'Pending',
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectMilestoneModal::class)
            ->call('load', $this->project->id)
            ->assertCount('items', 1)
            ->assertDontSeeHtml('wire:click="removeRow(0)"');
    }

    public function test_milestone_modal_shows_remove_button_when_multiple_instances(): void
    {
        Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'First Milestone',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'Pending',
            'sort_order' => 1,
        ]);
        Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Second Milestone',
            'due_date' => now()->addDays(20)->toDateString(),
            'status' => 'Pending',
            'sort_order' => 2,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectMilestoneModal::class)
            ->call('load', $this->project->id)
            ->assertCount('items', 2)
            ->assertSeeHtml('wire:click="removeRow(0)"')
            ->assertSeeHtml('wire:click="removeRow(1)"');
    }

    public function test_milestone_modal_prevents_removing_last_instance_with_validation_and_toast(): void
    {
        Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Only Milestone',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'Pending',
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectMilestoneModal::class)
            ->call('load', $this->project->id)
            ->assertCount('items', 1)
            ->call('removeRow', 0)
            ->assertCount('items', 1)
            ->assertHasErrors(['items'])
            ->assertDispatched('toast');
    }

    public function test_milestone_modal_prevents_empty_submission(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectMilestoneModal::class)
            ->call('load', $this->project->id)
            ->set('items', [])
            ->call('save')
            ->assertHasErrors(['items'])
            ->assertDispatched('toast');
    }

    public function test_schedule_modal_hides_remove_button_when_single_instance(): void
    {
        PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Single Advance',
            'amount' => 75000.00,
            'received' => 0.00,
            'tds' => 0.00,
            'status' => 'Pending',
            'trigger_type' => 'fixed',
            'due_date' => now()->toDateString(),
            'grace_days' => 0,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->assertCount('terms', 1)
            ->assertDontSeeHtml('wire:click="removeRow(0)"');
    }

    public function test_schedule_modal_shows_remove_button_when_multiple_instances(): void
    {
        PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Term 1',
            'amount' => 35000.00,
            'received' => 0.00,
            'tds' => 0.00,
            'status' => 'Pending',
            'trigger_type' => 'fixed',
            'due_date' => now()->toDateString(),
            'grace_days' => 0,
        ]);
        PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 2,
            'label' => 'Term 2',
            'amount' => 40000.00,
            'received' => 0.00,
            'tds' => 0.00,
            'status' => 'Pending',
            'trigger_type' => 'fixed',
            'due_date' => now()->addDays(20)->toDateString(),
            'grace_days' => 0,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->assertCount('terms', 2)
            ->assertSeeHtml('wire:click="removeRow(0)"')
            ->assertSeeHtml('wire:click="removeRow(1)"');
    }

    public function test_schedule_modal_prevents_removing_last_instance_with_validation_and_toast(): void
    {
        PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Only Term',
            'amount' => 75000.00,
            'received' => 0.00,
            'tds' => 0.00,
            'status' => 'Pending',
            'trigger_type' => 'fixed',
            'due_date' => now()->toDateString(),
            'grace_days' => 0,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->assertCount('terms', 1)
            ->call('removeRow', 0)
            ->assertCount('terms', 1)
            ->assertHasErrors(['terms'])
            ->assertDispatched('toast');
    }

    public function test_schedule_modal_prevents_empty_submission(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $this->project->id)
            ->set('terms', [])
            ->call('save')
            ->assertHasErrors(['terms'])
            ->assertDispatched('toast');
    }

    public function test_setting_index_hides_remove_button_when_single_reminder_day(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SettingIndex::class)
            ->set('reminderDays', [7])
            ->assertCount('reminderDays', 1)
            ->assertDontSeeHtml('wire:click="removeReminderDay(7)"');
    }

    public function test_setting_index_shows_remove_button_when_multiple_reminder_days(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SettingIndex::class)
            ->set('reminderDays', [7, 3])
            ->assertCount('reminderDays', 2)
            ->assertSeeHtml('wire:click="removeReminderDay(7)"')
            ->assertSeeHtml('wire:click="removeReminderDay(3)"');
    }

    public function test_setting_index_prevents_removing_last_reminder_day_with_validation_and_toast(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SettingIndex::class)
            ->set('reminderDays', [7])
            ->assertCount('reminderDays', 1)
            ->call('removeReminderDay', 7)
            ->assertCount('reminderDays', 1)
            ->assertHasErrors(['reminderDays'])
            ->assertDispatched('toast');
    }

    public function test_setting_index_prevents_saving_empty_reminder_days(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SettingIndex::class)
            ->set('reminderDays', [])
            ->call('saveSettings')
            ->assertHasErrors(['reminderDays'])
            ->assertDispatched('toast');
    }
}
