<?php

namespace Tests\Feature;

use App\Livewire\Customers\CustomerModal;
use App\Livewire\Followups\FollowupModal;
use App\Livewire\Payments\PaymentModal;
use App\Livewire\Projects\ProjectModal;
use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

class DummyValidationFormComponent extends Component
{
    public string $title = '';

    public string $email = '';

    public function updated($propertyName): void
    {
        $this->validateOnly($propertyName, [
            'email' => 'required|email',
        ]);
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|min:3',
            'email' => 'required|email',
        ]);
    }

    public function render()
    {
        return '<div>Dummy Form</div>';
    }
}

class ToastOnValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'must_change' => false,
        ]);
    }

    public function test_form_submission_dispatches_client_understandable_toast_on_validation_failure(): void
    {
        $test = Livewire::test(DummyValidationFormComponent::class)
            ->call('save');

        $test->assertHasErrors(['title', 'email'])
            ->assertDispatched('toast', function ($name, $params) {
                $msg = $params[0]['msg'] ?? '';
                $type = $params[0]['type'] ?? '';

                return str_starts_with($msg, 'Form not submitted:')
                    && str_contains($msg, 'The title field is required.')
                    && str_contains($msg, 'and 1 other error')
                    && $type === 'err';
            });
    }

    public function test_single_validation_error_displays_clean_singular_toast(): void
    {
        $test = Livewire::test(DummyValidationFormComponent::class)
            ->set('email', 'test@example.com')
            ->call('save');

        $test->assertHasErrors(['title'])
            ->assertDispatched('toast', function ($name, $params) {
                $msg = $params[0]['msg'] ?? '';
                $type = $params[0]['type'] ?? '';

                return $msg === 'Form not submitted: The title field is required.'
                    && $type === 'err';
            });
    }

    public function test_realtime_property_update_validation_does_not_dispatch_toast(): void
    {
        $test = Livewire::test(DummyValidationFormComponent::class)
            ->set('email', 'invalid-email');

        $test->assertHasErrors(['email'])
            ->assertNotDispatched('toast');
    }

    public function test_customer_modal_dispatches_toast_when_submission_fails(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CustomerModal::class)
            ->set('contact_name', '')
            ->set('phone', '')
            ->call('save')
            ->assertHasErrors(['contact_name', 'phone'])
            ->assertDispatched('toast', function ($name, $params) {
                $msg = $params[0]['msg'] ?? '';

                return str_starts_with($msg, 'Form not submitted:')
                    && ($params[0]['type'] ?? '') === 'err';
            });
    }

    public function test_project_modal_dispatches_toast_when_submission_fails(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectModal::class)
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name'])
            ->assertDispatched('toast', function ($name, $params) {
                $msg = $params[0]['msg'] ?? '';

                return str_starts_with($msg, 'Form not submitted:')
                    && ($params[0]['type'] ?? '') === 'err';
            });
    }

    public function test_payment_modal_dispatches_toast_when_submission_fails(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-TEST-001',
            'contact_name' => 'John Doe',
            'company' => 'Acme Test Corp',
            'status' => 'Active',
            'created_by' => $this->admin->id,
        ]);

        $project = Project::create([
            'project_code' => 'PJT-TEST-001',
            'name' => 'Test Project',
            'customer_id' => $customer->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 50000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaymentModal::class)
            ->set('project_id', $project->id)
            ->set('amount', null)
            ->call('save')
            ->assertHasErrors(['amount'])
            ->assertDispatched('toast', function ($name, $params) {
                $msg = $params[0]['msg'] ?? '';

                return str_starts_with($msg, 'Form not submitted:')
                    && ($params[0]['type'] ?? '') === 'err';
            });
    }

    public function test_followup_modal_dispatches_toast_when_submission_fails(): void
    {
        Livewire::actingAs($this->admin)
            ->test(FollowupModal::class)
            ->set('customer_id', null)
            ->set('summary', '')
            ->call('save')
            ->assertHasErrors(['customer_id', 'summary'])
            ->assertDispatched('toast', function ($name, $params) {
                $msg = $params[0]['msg'] ?? '';

                return str_starts_with($msg, 'Form not submitted:')
                    && ($params[0]['type'] ?? '') === 'err';
            });
    }
}
