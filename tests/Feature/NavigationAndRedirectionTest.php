<?php

namespace Tests\Feature;

use App\Livewire\Customers\CustomerDetail;
use App\Livewire\Documents\DocumentUploadModal;
use App\Livewire\Projects\ProjectDetail;
use App\Livewire\Projects\ProjectMilestoneModal;
use App\Livewire\Projects\ProjectModal;
use App\Livewire\Projects\ProjectScheduleModal;
use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class NavigationAndRedirectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $coordinator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'must_change' => false,
        ]);
        $this->coordinator = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
            'must_change' => false,
        ]);
    }

    public function test_customer_detail_retains_active_tab_from_query_string(): void
    {
        $this->actingAs($this->admin);
        $customer = Customer::create([
            'customer_code' => 'CUS-001',
            'company' => 'Acme Inc',
            'status' => 'Active',
        ]);

        Livewire::withQueryParams(['tab' => 'payments'])
            ->test(CustomerDetail::class, ['customer' => $customer])
            ->assertSet('activeTab', 'payments')
            ->assertSee('Payment schedule');
    }

    public function test_project_detail_retains_active_tab_from_query_string(): void
    {
        $this->actingAs($this->admin);
        $customer = Customer::create([
            'customer_code' => 'CUS-002',
            'company' => 'Beta Corp',
            'status' => 'Active',
        ]);
        $project = Project::create([
            'project_code' => 'PJT-001',
            'name' => 'Website Redesign',
            'customer_id' => $customer->id,
            'status' => 'In Progress',
            'health' => 'On Track',
        ]);

        Livewire::withQueryParams(['tab' => 'milestones'])
            ->test(ProjectDetail::class, ['project' => $project])
            ->assertSet('activeTab', 'milestones')
            ->assertSee('Add Milestone');
    }

    public function test_editing_project_does_not_force_redirect_to_index(): void
    {
        $this->actingAs($this->admin);
        $customer = Customer::create([
            'customer_code' => 'CUS-003',
            'company' => 'Gamma LLC',
            'status' => 'Active',
        ]);
        $project = Project::create([
            'project_code' => 'PJT-002',
            'name' => 'Original Name',
            'customer_id' => $customer->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->admin->id,
            'start_date' => now()->toDateString(),
        ]);

        Livewire::test(ProjectModal::class)
            ->call('loadProject', $project->id)
            ->set('name', 'Updated Project Name')
            ->call('save')
            ->assertDispatched('project-saved')
            ->assertDispatched('close-modal')
            ->assertNoRedirect();

        $this->assertEquals('Updated Project Name', $project->fresh()->name);
    }

    public function test_creating_project_redirects_to_new_project_show(): void
    {
        $this->actingAs($this->admin);
        $customer = Customer::create([
            'customer_code' => 'CUS-004',
            'company' => 'Delta Ltd',
            'status' => 'Active',
        ]);

        Livewire::test(ProjectModal::class)
            ->call('loadProject')
            ->set('name', 'Brand New Project')
            ->set('customer_id', $customer->id)
            ->set('coordinator_id', $this->coordinator->id)
            ->set('sales_person_id', $this->admin->id)
            ->set('value', 25000)
            ->set('cost', 10000)
            ->set('status', 'In Progress')
            ->set('health', 'On Track')
            ->call('save')
            ->assertDispatched('project-saved')
            ->assertRedirect();
    }

    public function test_project_schedule_and_milestones_update_in_place_without_redirect(): void
    {
        $this->actingAs($this->admin);
        $customer = Customer::create([
            'customer_code' => 'CUS-005',
            'company' => 'Epsilon Co',
            'status' => 'Active',
        ]);
        $project = Project::create([
            'project_code' => 'PJT-003',
            'name' => 'Milestone Test',
            'customer_id' => $customer->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'cost' => 50000,
        ]);

        Livewire::test(ProjectMilestoneModal::class)
            ->call('load', $project->id)
            ->call('save')
            ->assertDispatched('project-saved')
            ->assertDispatched('close-modal')
            ->assertNoRedirect();

        Livewire::test(ProjectScheduleModal::class)
            ->call('load', $project->id)
            ->set('terms.0.amount', 1000)
            ->set('terms.0.dueDate', '2026-11-01')
            ->call('save')
            ->assertDispatched('project-saved')
            ->assertDispatched('close-modal')
            ->assertNoRedirect();
    }

    public function test_document_upload_modal_closes_without_forced_redirect(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);
        $customer = Customer::create([
            'customer_code' => 'CUS-006',
            'company' => 'Zeta Org',
            'status' => 'Active',
        ]);

        Livewire::test(DocumentUploadModal::class)
            ->set('customer_id', $customer->id)
            ->set('title', 'Test Agreement')
            ->set('category', 'Contract')
            ->set('file', UploadedFile::fake()->create('agreement.pdf', 100, 'application/pdf'))
            ->call('upload')
            ->assertDispatched('document-saved')
            ->assertDispatched('close-modal')
            ->assertNoRedirect();
    }
}
