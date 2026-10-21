<?php

namespace Tests\Feature;

use App\Livewire\Payments\PaymentModal;
use App\Livewire\Projects\ProjectModal;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Services\CodeGeneratorService;
use Database\Seeders\CustomersSeeder;
use Database\Seeders\PaymentsSeeder;
use Database\Seeders\ProjectsSeeder;
use Database\Seeders\UsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CodeGeneratorAndSeederSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_synchronize_counters_table(): void
    {
        $this->seed(UsersSeeder::class);
        $this->seed(CustomersSeeder::class);
        $this->seed(ProjectsSeeder::class);
        $this->seed(PaymentsSeeder::class);

        $customerCounter = DB::table('counters')->where('name', 'customer')->value('value');
        $projectCounter = DB::table('counters')->where('name', 'project')->value('value');
        $paymentCounter = DB::table('counters')->where('name', 'payment')->value('value');

        $this->assertNotNull($customerCounter);
        $this->assertGreaterThanOrEqual(7, $customerCounter);

        $this->assertNotNull($projectCounter);
        $this->assertGreaterThanOrEqual(8, $projectCounter);

        $this->assertNotNull($paymentCounter);
        $this->assertGreaterThanOrEqual(10, $paymentCounter);
    }

    public function test_code_generator_self_heals_when_counters_are_desynchronized(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $customer = Customer::create([
            'customer_code' => 'CUS-0001',
            'company' => 'Acme Test Corp',
            'status' => 'Active',
        ]);

        Project::create([
            'project_code' => 'PJT-0008',
            'customer_id' => $customer->id,
            'name' => 'Existing High Project',
            'value' => 500000,
            'status' => 'In Progress',
            'health' => 'On Track',
        ]);

        // Artificially desynchronize the counter to 2 (as if only PJT-0002 had been recorded)
        DB::table('counters')->updateOrInsert(['name' => 'project'], ['value' => 2]);

        $service = app(CodeGeneratorService::class);
        $nextCode = $service->generate('project', 'PJT-');

        // Should self-heal past PJT-0008 and return PJT-0009
        $this->assertEquals('PJT-0009', $nextCode);

        // Counter in table should now be 9
        $updatedCounter = DB::table('counters')->where('name', 'project')->value('value');
        $this->assertEquals(9, $updatedCounter);
    }

    public function test_project_and_payment_modal_creation_succeeds_without_unique_constraint_violation(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $salesRep = User::factory()->create(['role' => 'Sales', 'status' => 'Active']);
        $coordinator = User::factory()->create(['role' => 'Coordinator', 'status' => 'Active']);
        $customer = Customer::create([
            'customer_code' => 'CUS-0001',
            'company' => 'Modal Test Client',
            'status' => 'Active',
        ]);

        Project::create([
            'project_code' => 'PJT-0005',
            'customer_id' => $customer->id,
            'sales_person_id' => $salesRep->id,
            'coordinator_id' => $coordinator->id,
            'name' => 'Seeded Collision Dummy',
            'value' => 100000,
            'status' => 'In Progress',
            'health' => 'On Track',
        ]);

        // Counter is behind at 4 (which previously caused collision with PJT-0005)
        DB::table('counters')->updateOrInsert(['name' => 'project'], ['value' => 4]);

        Livewire::actingAs($admin)
            ->test(ProjectModal::class)
            ->call('loadProject', null)
            ->set('name', 'Brand New Safe Project')
            ->set('customer_id', $customer->id)
            ->set('sales_person_id', $salesRep->id)
            ->set('coordinator_id', $coordinator->id)
            ->set('value', 250000)
            ->set('status', 'In Progress')
            ->set('health', 'On Track')
            ->call('save')
            ->assertHasNoErrors();

        $newProject = Project::where('name', 'Brand New Safe Project')->first();
        $this->assertNotNull($newProject);
        $this->assertEquals('PJT-0006', $newProject->project_code);

        // Test Payment creation with desynchronized counter
        Payment::create([
            'code' => 'RCP-0003',
            'project_id' => $newProject->id,
            'customer_id' => $customer->id,
            'amount' => 50000,
            'paid_on' => now()->toDateString(),
            'mode' => 'NEFT',
        ]);

        DB::table('counters')->updateOrInsert(['name' => 'payment'], ['value' => 2]);

        Livewire::actingAs($admin)
            ->test(PaymentModal::class)
            ->call('openPaymentModal', $newProject->id)
            ->set('amount', 30000)
            ->set('paid_on', now()->toDateString())
            ->set('mode', 'UPI')
            ->call('save')
            ->assertHasNoErrors();

        $newPayment = Payment::where('amount', 30000)->where('mode', 'UPI')->first();
        $this->assertNotNull($newPayment);
        $this->assertEquals('RCP-0004', $newPayment->code);
    }
}
