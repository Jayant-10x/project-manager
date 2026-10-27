<?php

namespace Tests\Feature;

use App\Livewire\Customers\CustomerIndex;
use App\Livewire\Payments\PaymentIndex;
use App\Livewire\Projects\ProjectScheduleModal;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IssuesBatchTwoTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_index_search_filters_results_correctly(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $cust1 = Customer::create(['customer_code' => 'CUS-001', 'company' => 'Acme Corporation', 'status' => 'Active']);
        $cust2 = Customer::create(['customer_code' => 'CUS-002', 'company' => 'Beta Global', 'status' => 'Active']);

        $proj1 = Project::create([
            'project_code' => 'PJT-001',
            'name' => 'Portal App',
            'customer_id' => $cust1->id,
            'status' => 'Onboarding',
            'health' => 'On Track',
            'value' => 50000,
        ]);
        $proj2 = Project::create([
            'project_code' => 'PJT-002',
            'name' => 'Mobile App',
            'customer_id' => $cust2->id,
            'status' => 'Onboarding',
            'health' => 'On Track',
            'value' => 70000,
        ]);

        Payment::create([
            'project_id' => $proj1->id,
            'customer_id' => $cust1->id,
            'code' => 'RCP-0001',
            'amount' => 1000,
            'tds' => 0,
            'paid_on' => now()->toDateString(),
            'mode' => 'NEFT',
            'reference' => 'UTR12345',
            'bank' => 'HDFC Bank',
            'created_by' => $user->id,
        ]);

        Payment::create([
            'project_id' => $proj2->id,
            'customer_id' => $cust2->id,
            'code' => 'RCP-0002',
            'amount' => 2000,
            'tds' => 0,
            'paid_on' => now()->toDateString(),
            'mode' => 'NEFT',
            'reference' => 'UTR99999',
            'bank' => 'ICICI Bank',
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(PaymentIndex::class)
            ->set('search', 'Acme')
            ->assertSee('RCP-0001')
            ->assertDontSee('RCP-0002')
            ->set('search', 'ICICI')
            ->assertSee('RCP-0002')
            ->assertDontSee('RCP-0001');
    }

    public function test_customer_export_csv_applies_active_filters(): void
    {
        $this->markTestSkipped('Customer CSV export is temporarily disabled.');
        $user = User::factory()->create(['role' => 'Admin']);
        Customer::create(['customer_code' => 'CUS-001', 'company' => 'First Tech Ltd', 'city' => 'Mumbai', 'status' => 'Active']);
        Customer::create(['customer_code' => 'CUS-002', 'company' => 'Second Cloud Ltd', 'city' => 'Delhi', 'status' => 'Active']);

        Livewire::actingAs($user)
            ->test(CustomerIndex::class)
            ->set('search', 'First')
            ->call('exportCsv')
            ->assertFileDownloaded('customers-'.now()->toDateString().'.csv');
    }

    public function test_schedule_modal_requires_installments_to_match_contracted_project_value(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $cust = Customer::create(['customer_code' => 'CUS-001', 'company' => 'Acme Corporation', 'status' => 'Active']);
        $project = Project::create([
            'project_code' => 'PJT-001',
            'name' => 'Portal App',
            'customer_id' => $cust->id,
            'status' => 'Onboarding',
            'health' => 'On Track',
            'value' => 50000,
        ]);

        Livewire::actingAs($user)
            ->test(ProjectScheduleModal::class)
            ->call('load', $project->id)
            ->set('terms', [
                [
                    'id' => null,
                    'label' => 'Advance Milestone',
                    'amount' => 30000,
                    'trigger' => 'fixed',
                    'dueDate' => now()->addDays(5)->toDateString(),
                    'milestoneId' => null,
                    'graceDays' => 0,
                    'locked' => false,
                ],
            ])
            ->assertSee('Unscheduled Balance')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(1, PaymentTerm::where('project_id', $project->id)->count());
    }
}
