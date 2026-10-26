<?php

namespace Tests\Feature;

use App\Livewire\Customers\CustomerModal;
use App\Livewire\Payments\PaymentModal;
use App\Livewire\Projects\ProjectModal;
use App\Livewire\Settings\UserModal;
use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormErrorAutoScrollAndFocusTest extends TestCase
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
            'must_change' => false,
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUS-ERR-1',
            'contact_name' => 'Test Corp',
            'phone' => '9876543210',
            'state' => 'MH',
            'status' => 'Active',
            'created_by' => $this->admin->id,
        ]);

        $this->project = Project::create([
            'project_code' => 'PJT-ERR-1',
            'customer_id' => $this->customer->id,
            'name' => 'Auto Scroll Test App',
            'value' => 50000.00,
            'status' => 'In Progress',
            'health' => 'On Track',
            'coordinator_id' => $this->admin->id,
            'start_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(30)->toDateString(),
        ]);
    }

    public function test_app_layout_renders_auto_scroll_and_focus_engine(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertStatus(200);

        // Verify the auto-scroll engine is present
        $response->assertSee('window.scrollToFirstError = function', false);
        $response->assertSee('setupLivewireErrorScroller', false);
        $response->assertSee('error-focused', false);
        $response->assertSee('scrollIntoView', false);
    }

    public function test_crm_stylesheet_contains_error_pulse_animation(): void
    {
        $css = file_get_contents(public_path('css/crm-style.css'));
        $this->assertStringContainsString('errorFieldPulse', $css);
        $this->assertStringContainsString('.error-focused', $css);
    }

    public function test_project_modal_validation_outputs_targetable_error_elements(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectModal::class)
            ->call('loadProject')
            ->set('name', '') // invalid empty
            ->set('value', '') // invalid empty
            ->call('save')
            ->assertHasErrors(['name', 'value'])
            ->assertSee('border-red-500!', false);
    }

    public function test_customer_modal_validation_outputs_targetable_error_elements(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CustomerModal::class)
            ->call('loadCustomer')
            ->set('contact_name', '') // invalid empty
            ->set('phone', '') // invalid empty
            ->call('save')
            ->assertHasErrors(['contact_name', 'phone'])
            ->assertSee('border-red-500!', false);
    }

    public function test_payment_modal_validation_outputs_targetable_error_elements(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PaymentModal::class)
            ->call('openPaymentModal')
            ->set('project_id', null)
            ->set('amount', null)
            ->call('save')
            ->assertHasErrors(['project_id', 'amount'])
            ->assertSee('border-red-500!', false);
    }

    public function test_user_modal_validation_outputs_targetable_error_elements(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UserModal::class)
            ->call('openModal')
            ->set('name', '')
            ->set('email', '')
            ->call('save')
            ->assertHasErrors(['name', 'email'])
            ->assertSee('border-red-500!', false);
    }
}
