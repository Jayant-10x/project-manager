<?php

namespace Tests\Feature;

use App\Livewire\Customers\CustomerDetail;
use App\Livewire\Projects\ProjectDetail;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Followup;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RbacDeletionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $coordinator;

    protected User $sales;

    protected Customer $customer;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
        Storage::fake('public');

        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'must_change' => false,
        ]);

        $this->coordinator = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
            'name' => 'Coord User',
            'email' => 'coord@test.com',
            'must_change' => false,
            'reporting_to' => $this->admin->id,
        ]);

        $this->sales = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Active',
            'name' => 'Sales User',
            'email' => 'sales@test.com',
            'must_change' => false,
            'reporting_to' => $this->coordinator->id,
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUS-RBAC-1',
            'contact_name' => 'RBAC Test Customer',
            'phone' => '9876543210',
            'state' => 'MH',
            'status' => 'Active',
            'approval_status' => 'Approved',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->admin->id,
        ]);

        $this->project = Project::create([
            'project_code' => 'PJT-RBAC-1',
            'customer_id' => $this->customer->id,
            'name' => 'RBAC Project',
            'value' => 50000.00,
            'start_date' => Carbon::today()->toDateString(),
            'status' => 'In Progress',
            'sales_person_id' => $this->sales->id,
            'coordinator_id' => $this->coordinator->id,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_non_admin_cannot_delete_payment_in_customer_detail(): void
    {
        $term = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Advance Term',
            'amount' => 10000.00,
            'received' => 10000.00,
            'balance' => 0.00,
            'status' => 'Paid',
            'trigger_type' => 'fixed',
        ]);

        $payment = Payment::create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'term_id' => $term->id,
            'amount' => 10000.00,
            'paid_on' => Carbon::today()->toDateString(),
            'mode' => 'NEFT',
            'code' => 'REC-RBAC-01',
            'created_by' => $this->admin->id,
        ]);

        // Attempt direct Livewire deletion as non-admin (Sales)
        Livewire::actingAs($this->sales)
            ->test(CustomerDetail::class, ['customer' => $this->customer])
            ->call('deletePayment', $payment->id)
            ->assertDispatched('toast', ['msg' => 'Only Admin can delete payments.', 'type' => 'err']);

        $this->assertDatabaseHas('payments', ['id' => $payment->id]);

        // Coordinator viewing payments tab cannot see delete button
        Livewire::actingAs($this->coordinator)
            ->test(CustomerDetail::class, ['customer' => $this->customer])
            ->set('activeTab', 'payments')
            ->assertDontSee('deletePayment', false)
            ->call('deletePayment', $payment->id)
            ->assertDispatched('toast', ['msg' => 'Only Admin can delete payments.', 'type' => 'err']);

        $this->assertDatabaseHas('payments', ['id' => $payment->id]);

        // Admin sees delete button and can delete payment
        Livewire::actingAs($this->admin)
            ->test(CustomerDetail::class, ['customer' => $this->customer])
            ->set('activeTab', 'payments')
            ->assertSee('deletePayment', false)
            ->call('deletePayment', $payment->id)
            ->assertDispatched('payment-deleted');

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_non_admin_cannot_delete_payment_in_project_detail(): void
    {
        $term = PaymentTerm::create([
            'project_id' => $this->project->id,
            'seq' => 1,
            'label' => 'Advance Term',
            'amount' => 10000.00,
            'received' => 10000.00,
            'balance' => 0.00,
            'status' => 'Paid',
            'trigger_type' => 'fixed',
        ]);

        $payment = Payment::create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'term_id' => $term->id,
            'amount' => 10000.00,
            'paid_on' => Carbon::today()->toDateString(),
            'mode' => 'NEFT',
            'code' => 'REC-RBAC-02',
            'created_by' => $this->admin->id,
        ]);

        // Coordinator viewing payments tab does not see delete button in UI and cannot delete
        Livewire::actingAs($this->coordinator)
            ->test(ProjectDetail::class, ['project' => $this->project])
            ->set('activeTab', 'payments')
            ->assertDontSee('deletePayment', false)
            ->call('deletePayment', $payment->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('payments', ['id' => $payment->id]);

        // Admin sees delete button and can delete payment
        Livewire::actingAs($this->admin)
            ->test(ProjectDetail::class, ['project' => $this->project])
            ->set('activeTab', 'payments')
            ->assertSee('deletePayment', false)
            ->call('deletePayment', $payment->id)
            ->assertDispatched('payment-deleted');

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_document_deletion_authorization_in_customer_and_project_detail(): void
    {
        $doc = Document::create([
            'customer_id' => $this->customer->id,
            'project_id' => $this->project->id,
            'title' => 'Sensitive Contract',
            'file_name' => 'sensitive_contract.pdf',
            'stored_as' => 'sensitive_contract.pdf',
            'file_size' => 1024,
            'category' => 'Contract',
            'uploaded_by' => $this->sales->id,
        ]);

        // Coordinator (not admin, not uploader) tries to delete customer doc
        Livewire::actingAs($this->coordinator)
            ->test(CustomerDetail::class, ['customer' => $this->customer])
            ->set('activeTab', 'documents')
            ->assertDontSee('deleteDocument', false)
            ->call('deleteDocument', $doc->id)
            ->assertDispatched('toast', ['msg' => 'You can only remove files you uploaded.', 'type' => 'err']);

        $this->assertDatabaseHas('documents', ['id' => $doc->id]);

        // Coordinator (not admin, not uploader) tries to delete project doc
        Livewire::actingAs($this->coordinator)
            ->test(ProjectDetail::class, ['project' => $this->project])
            ->set('activeTab', 'documents')
            ->assertDontSee('deleteDocument', false)
            ->call('deleteDocument', $doc->id)
            ->assertDispatched('toast', ['msg' => 'You can only remove files you uploaded.', 'type' => 'err']);

        $this->assertDatabaseHas('documents', ['id' => $doc->id]);

        // Sales (uploader) can see button and delete doc
        Livewire::actingAs($this->sales)
            ->test(CustomerDetail::class, ['customer' => $this->customer])
            ->set('activeTab', 'documents')
            ->assertSee('deleteDocument', false)
            ->call('deleteDocument', $doc->id)
            ->assertDispatched('toast', ['msg' => 'Document removed', 'type' => 'ok']);

        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);

        // Recreate doc uploaded by sales, admin can see button and delete it
        $doc2 = Document::create([
            'customer_id' => $this->customer->id,
            'project_id' => $this->project->id,
            'title' => 'Invoice File',
            'file_name' => 'invoice.pdf',
            'stored_as' => 'invoice.pdf',
            'file_size' => 2048,
            'category' => 'Invoice',
            'uploaded_by' => $this->sales->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectDetail::class, ['project' => $this->project])
            ->set('activeTab', 'documents')
            ->assertSee('deleteDocument', false)
            ->call('deleteDocument', $doc2->id)
            ->assertDispatched('toast', ['msg' => 'Document removed', 'type' => 'ok']);

        $this->assertDatabaseMissing('documents', ['id' => $doc2->id]);
    }

    public function test_followup_deletion_authorization_in_customer_detail(): void
    {
        $followup = Followup::create([
            'customer_id' => $this->customer->id,
            'type' => 'Call',
            'date' => Carbon::today()->toDateString(),
            'summary' => 'Client called regarding project scope',
            'created_by' => $this->sales->id,
        ]);

        // Coordinator (not admin, not creator) tries to delete followup
        Livewire::actingAs($this->coordinator)
            ->test(CustomerDetail::class, ['customer' => $this->customer])
            ->call('deleteFollowup', $followup->id)
            ->assertDispatched('toast', ['msg' => 'You can only remove follow-ups you logged.', 'type' => 'err']);

        $this->assertDatabaseHas('followups', ['id' => $followup->id]);

        // Sales (creator) can delete followup
        Livewire::actingAs($this->sales)
            ->test(CustomerDetail::class, ['customer' => $this->customer])
            ->call('deleteFollowup', $followup->id)
            ->assertDispatched('toast', ['msg' => 'Follow-up removed', 'type' => 'ok']);

        $this->assertDatabaseMissing('followups', ['id' => $followup->id]);

        // Recreate followup by sales, admin can delete it
        $followup2 = Followup::create([
            'customer_id' => $this->customer->id,
            'type' => 'Meeting',
            'date' => Carbon::today()->toDateString(),
            'summary' => 'In-person meeting summary',
            'created_by' => $this->sales->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CustomerDetail::class, ['customer' => $this->customer])
            ->call('deleteFollowup', $followup2->id)
            ->assertDispatched('toast', ['msg' => 'Follow-up removed', 'type' => 'ok']);

        $this->assertDatabaseMissing('followups', ['id' => $followup2->id]);
    }
}
