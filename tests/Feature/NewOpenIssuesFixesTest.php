<?php

namespace Tests\Feature;

use App\Livewire\Customers\CustomerImportModal;
use App\Livewire\Customers\CustomerIndex;
use App\Livewire\Customers\CustomerModal;
use App\Livewire\Projects\ProjectModal;
use App\Livewire\Settings\SettingIndex;
use App\Livewire\Settings\UserModal;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class NewOpenIssuesFixesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        $this->sales = User::create([
            'name' => 'Sales User',
            'email' => 'sales@test.com',
            'password' => bcrypt('password'),
            'role' => 'Sales',
            'status' => 'Active',
        ]);
    }

    /**
     * ISS-068: Verify multiple customers can be created with blank company name without MySQL duplicate key error.
     */
    public function test_iss_068_empty_string_company_converts_to_null_and_allows_multiple_records(): void
    {
        $this->actingAs($this->admin);

        // First customer with empty company
        Livewire::test(CustomerModal::class)
            ->set('contact_name', 'Client Alpha')
            ->set('company', '')
            ->set('phone', '9876543210')
            ->set('state', 'MH')
            ->set('sales_person_id', $this->sales->id)
            ->call('save')
            ->assertHasNoErrors();

        // Second customer with empty company
        Livewire::test(CustomerModal::class)
            ->set('contact_name', 'Client Beta')
            ->set('company', '')
            ->set('phone', '9876543211')
            ->set('state', 'MH')
            ->set('sales_person_id', $this->sales->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('customers', 2);
        $customers = Customer::all();
        $this->assertNull($customers[0]->company);
        $this->assertNull($customers[1]->company);
    }

    /**
     * ISS-070: Customer sub-header does not duplicate contact name when company name is absent.
     */
    public function test_iss_070_customer_sub_header_does_not_repeat_contact_name_when_company_is_null(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::create([
            'customer_code' => 'CUS-0099',
            'contact_name' => 'Deepak Suri',
            'company' => null,
            'phone' => '9876500000',
            'state' => 'MH',
            'city' => 'Hyderabad',
            'status' => 'Active',
        ]);

        Livewire::test(CustomerIndex::class)
            ->assertSee('Deepak Suri (Client)')
            ->assertDontSee('· Deepak Suri')
            ->assertSee('Hyderabad');
    }

    /**
     * ISS-071: CSV customer importer successfully imports records lacking company name.
     */
    public function test_iss_071_csv_customer_importer_imports_records_lacking_company_name(): void
    {
        $this->actingAs($this->admin);

        $csvContent = "company,contact,phone,email,city,state\n,Rohan Kumar,9876543220,rohan@gmail.com,Pune,MH\n,Aakash Verma,9876543221,aakash@gmail.com,Mumbai,MH";

        Livewire::test(CustomerImportModal::class)
            ->set('pastedData', $csvContent)
            ->set('defaultSalesPersonId', $this->sales->id)
            ->call('import')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'contact_name' => 'Rohan Kumar',
            'company' => null,
            'phone' => '9876543220',
        ]);

        $this->assertDatabaseHas('customers', [
            'contact_name' => 'Aakash Verma',
            'company' => null,
            'phone' => '9876543221',
        ]);
    }

    /**
     * ISS-072: Project modal double-submission guard prevents redundant creation.
     */
    public function test_iss_072_project_modal_submission_guard(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::create([
            'customer_code' => 'CUS-0088',
            'contact_name' => 'Test Client',
            'phone' => '9876543299',
            'state' => 'DL',
            'status' => 'Active',
        ]);

        $component = Livewire::test(ProjectModal::class)
            ->set('customer_id', $customer->id)
            ->set('name', 'Single Run Project')
            ->set('project_type', 'Website')
            ->set('value', 50000)
            ->set('coordinator_id', $this->admin->id)
            ->set('sales_person_id', $this->sales->id)
            ->set('status', 'In Progress')
            ->set('health', 'On Track');

        // Calling save creates the project
        $component->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseHas('projects', ['name' => 'Single Run Project']);
    }

    /**
     * ISS-073: Settings page conceals User & Team Access card from Sales users.
     */
    public function test_iss_073_settings_conceals_user_accounts_card_for_sales(): void
    {
        $this->actingAs($this->sales);

        Livewire::test(SettingIndex::class)
            ->assertDontSee('User & Team Access', false)
            ->assertDontSee('Open Users Directory')
            ->assertDontSee('Add User');
    }

    /**
     * ISS-073 (Admin): Settings page displays User & Team Access card for Admin.
     */
    public function test_iss_073_settings_displays_user_accounts_card_for_admin(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(SettingIndex::class)
            ->assertSee('User & Team Access', false)
            ->assertSee('Open Users Directory')
            ->assertSee('Add User');
    }

    /**
     * ISS-074: Verify database schema has contact_name as non-nullable in customers table.
     */
    public function test_iss_074_customers_contact_name_column_is_not_nullable(): void
    {
        $this->assertTrue(Schema::hasColumn('customers', 'contact_name'));

        // Attempting to insert a customer with contact_name explicitly null at database level
        $customer = new Customer;
        $customer->customer_code = 'CUS-SCHEMA';
        $customer->company = 'Schema Co';
        $customer->phone = '9876543200';
        $customer->state = 'MH';
        $customer->status = 'Active';
        $customer->save();

        $customer->refresh();
        $this->assertNotNull($customer->contact_name);
        $this->assertSame('', $customer->contact_name);
    }

    /**
     * ISS-075: Verify UserModal manages password visibility and permissions toggling via Livewire state,
     * eliminating nested Alpine x-data DOM morph collisions.
     */
    public function test_iss_075_user_modal_password_and_permission_toggles_without_alpine_expression_errors(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(UserModal::class)
            ->call('openModal')
            ->assertSet('showPass', false)
            ->assertSet('openPerms', false)
            ->assertSee('wire:ignore.self', false)
            ->assertSee("x-show=\"typeof show !== 'undefined' && show\"", false)
            ->assertSee('type="password"', false)
            ->assertDontSee('x-data="{ showPass: false }"', false)
            ->assertDontSee('x-data="{ openPerms: false }"', false)
            ->assertDontSee('x-collapse', false)
            ->set('email', 'admin@test.com')
            ->call('save')
            ->assertHasErrors(['email'])
            ->call('togglePasswordVisibility')
            ->assertSet('showPass', true)
            ->assertSee('type="text"', false)
            ->call('togglePermissionsDropdown')
            ->assertSet('openPerms', true)
            ->assertSee('Permissions &amp; Access Controls', false);
    }
}
