<?php

namespace Tests\Feature;

use App\Livewire\Projects\ProjectModal;
use App\Livewire\Projects\ProjectScheduleModal;
use App\Livewire\Settings\UserModal;
use App\Models\Customer;
use App\Models\Milestone;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class FormSubmissionsAndModalWiringTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_split_gracefully_populates_staggered_dates_without_milestones(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $customer = Customer::create([
            'customer_code' => 'CUS-0001',
            'company' => 'Schedule Test Customer',
            'status' => 'Active',
        ]);

        $project = Project::create([
            'project_code' => 'PJT-0001',
            'customer_id' => $customer->id,
            'name' => 'AutoSplit Test Project',
            'value' => 300000.00,
            'status' => 'In Progress',
            'health' => 'On Track',
            'start_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(60)->toDateString(),
        ]);

        Livewire::actingAs($admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $project->id)
            ->call('autoSplit')
            ->call('save')
            ->assertHasNoErrors();

        $terms = PaymentTerm::where('project_id', $project->id)->orderBy('seq')->get();
        $this->assertCount(3, $terms);
        $this->assertEquals(90000.00, (float) $terms[0]->amount);
        $this->assertEquals(120000.00, (float) $terms[1]->amount);
        $this->assertEquals(90000.00, (float) $terms[2]->amount);

        // Ensure all terms have valid due dates since no milestones exist
        $this->assertNotNull($terms[0]->due_date);
        $this->assertNotNull($terms[1]->due_date);
        $this->assertNotNull($terms[2]->due_date);
    }

    public function test_auto_split_links_milestones_when_milestones_exist(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $customer = Customer::create([
            'customer_code' => 'CUS-0002',
            'company' => 'Milestone Test Customer',
            'status' => 'Active',
        ]);

        $project = Project::create([
            'project_code' => 'PJT-0002',
            'customer_id' => $customer->id,
            'name' => 'Milestone AutoSplit Project',
            'value' => 500000.00,
            'status' => 'In Progress',
            'health' => 'On Track',
        ]);

        $m1 = Milestone::create([
            'project_id' => $project->id,
            'name' => 'Design Phase Approval',
            'seq' => 1,
            'status' => 'Pending',
        ]);

        $m2 = Milestone::create([
            'project_id' => $project->id,
            'name' => 'Final UAT Deployment',
            'seq' => 2,
            'status' => 'Pending',
        ]);

        Livewire::actingAs($admin)
            ->test(ProjectScheduleModal::class)
            ->call('load', $project->id)
            ->call('autoSplit')
            ->call('save')
            ->assertHasNoErrors();

        $terms = PaymentTerm::where('project_id', $project->id)->orderBy('seq')->get();
        $this->assertCount(3, $terms);
        $this->assertEquals('fixed', $terms[0]->trigger_type);
        $this->assertEquals('milestone', $terms[1]->trigger_type);
        $this->assertEquals($m1->id, $terms[1]->milestone_id);
        $this->assertEquals('milestone', $terms[2]->trigger_type);
        $this->assertEquals($m2->id, $terms[2]->milestone_id);
    }

    public function test_project_modal_preselects_customer_id_when_dispatched_from_customer_detail(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $customer = Customer::create([
            'customer_code' => 'CUS-0003',
            'company' => 'Target Customer Inc',
            'status' => 'Active',
        ]);

        Livewire::actingAs($admin)
            ->test(ProjectModal::class)
            ->call('loadProject', null, $customer->id)
            ->assertSet('projectId', null)
            ->assertSet('customer_id', $customer->id);
    }

    public function test_user_modal_creation_allows_blank_password_and_applies_default(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        // 1. Leave password blank on create
        Livewire::actingAs($admin)
            ->test(UserModal::class)
            ->call('openModal', null)
            ->set('name', 'Priya Sharma')
            ->set('email', 'priya.sharma@example.com')
            ->set('role', 'Coordinator')
            ->set('status', 'Active')
            ->set('password', '')
            ->call('save')
            ->assertHasNoErrors();

        $newUser = User::where('email', 'priya.sharma@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue(Hash::check('Welcome@10x', $newUser->password));
        $this->assertTrue((bool) $newUser->must_change);

        // 2. Custom password matching rules
        Livewire::actingAs($admin)
            ->test(UserModal::class)
            ->call('openModal', null)
            ->set('name', 'Vikram Rao')
            ->set('email', 'vikram.rao@example.com')
            ->set('role', 'Sales')
            ->set('status', 'Active')
            ->set('password', 'SecretPass#123')
            ->call('save')
            ->assertHasNoErrors();

        $customUser = User::where('email', 'vikram.rao@example.com')->first();
        $this->assertNotNull($customUser);
        $this->assertTrue(Hash::check('SecretPass#123', $customUser->password));

        // 3. Invalid password fails validation
        Livewire::actingAs($admin)
            ->test(UserModal::class)
            ->call('openModal', null)
            ->set('name', 'Bad Password User')
            ->set('email', 'bad.pass@example.com')
            ->set('role', 'Coordinator')
            ->set('status', 'Active')
            ->set('password', 'short')
            ->call('save')
            ->assertHasErrors(['password']);
    }
}
