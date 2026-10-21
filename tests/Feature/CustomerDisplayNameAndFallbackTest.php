<?php

namespace Tests\Feature;

use App\Livewire\Components\GlobalSearch;
use App\Livewire\Customers\CustomerModal;
use App\Livewire\Projects\ProjectModal;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerDisplayNameAndFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $coordinator;

    protected User $sales;

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
        $this->sales = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Active',
            'must_change' => false,
        ]);
    }

    public function test_display_name_accessor_returns_company_when_present(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-101',
            'company' => 'Acme Corporation Ltd',
            'contact_name' => 'Alice Smith',
            'status' => 'Active',
        ]);

        $this->assertEquals('Acme Corporation Ltd', $customer->display_name);
        $this->assertEquals('Acme Corporation Ltd', $customer->name);
    }

    public function test_display_name_accessor_falls_back_to_contact_name_with_client_suffix(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-102',
            'company' => null,
            'contact_name' => 'Bob Builder',
            'status' => 'Active',
        ]);

        $this->assertEquals('Bob Builder (Client)', $customer->display_name);
        $this->assertEquals('Bob Builder (Client)', $customer->name);
    }

    public function test_customer_can_be_saved_without_company_name(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CustomerModal::class)
            ->call('loadCustomer', null)
            ->set('company', '')
            ->set('contact_name', 'Rohan Sharma')
            ->set('phone', '9876543210')
            ->set('state', 'MH')
            ->set('sales_person_id', $this->sales->id)
            ->set('status', 'Active')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('customer-saved');

        $customer = Customer::where('contact_name', 'Rohan Sharma')->first();
        $this->assertNotNull($customer);
        $this->assertNull($customer->company);
        $this->assertEquals('Rohan Sharma (Client)', $customer->display_name);
    }

    public function test_global_search_displays_client_suffix_when_company_is_null(): void
    {
        Customer::create([
            'customer_code' => 'CUS-103',
            'company' => null,
            'contact_name' => 'Sneha Patel',
            'status' => 'Active',
        ]);

        Livewire::actingAs($this->admin)
            ->test(GlobalSearch::class)
            ->set('query', 'Sneha')
            ->assertSee('Sneha Patel (Client)');
    }

    public function test_project_modal_renders_customer_display_name_in_options(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-104',
            'company' => null,
            'contact_name' => 'Tanya Verma',
            'status' => 'Active',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectModal::class)
            ->call('loadProject', null)
            ->assertSee('Tanya Verma (Client)');
    }
}
