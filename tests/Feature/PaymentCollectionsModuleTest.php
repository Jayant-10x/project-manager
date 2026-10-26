<?php

namespace Tests\Feature;

use App\Livewire\Payments\PaymentIndex;
use App\Livewire\Payments\PaymentModal;
use App\Livewire\Projects\MilestoneChaseModal;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Followup;
use App\Models\Milestone;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentCollectionsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $coordinator;

    protected User $sales;

    protected Customer $customer;

    protected Project $project;

    protected Milestone $milestone;

    protected PaymentTerm $term;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('uploads');
        Storage::fake('public');

        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'must_change' => false,
        ]);

        $this->manager = User::factory()->create([
            'role' => 'Manager',
            'status' => 'Active',
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'must_change' => false,
            'reporting_to' => $this->admin->id,
        ]);

        $this->coordinator = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
            'name' => 'Coord User',
            'email' => 'coord@test.com',
            'must_change' => false,
            'reporting_to' => $this->manager->id,
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
            'customer_code' => 'CUS-PAY-1',
            'contact_name' => 'Global Retailers Ltd',
            'phone' => '9876543210',
            'state' => 'MH',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $this->project = Project::create([
            'project_code' => 'PJT-PAY-1',
            'customer_id' => $this->customer->id,
            'name' => 'E-Commerce Platform',
            'value' => 100000.00,
            'cost' => 30000.00,
            'status' => 'In Progress',
            'health' => 'On Track',
            'coordinator_id' => $this->coordinator->id,
            'sales_person_id' => $this->sales->id,
            'start_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(60)->toDateString(),
        ]);

        $this->milestone = Milestone::create([
            'project_id' => $this->project->id,
            'name' => 'Phase 1 - Design & Prototype',
            'due_date' => now()->addDays(15)->toDateString(),
            'status' => 'Pending',
            'sort_order' => 1,
        ]);

        $this->term = PaymentTerm::create([
            'project_id' => $this->project->id,
            'milestone_id' => $this->milestone->id,
            'label' => 'Advance Milestone Payment',
            'amount' => 40000.00,
            'received' => 0.00,
            'tds' => 0.00,
            'status' => 'Pending',
            'seq' => 1,
            'percent' => 40.00,
            'due_date' => now()->addDays(15)->toDateString(),
        ]);
    }

    public function test_payment_index_is_accessible_to_authorized_roles_and_forbidden_to_sales(): void
    {
        $this->actingAs($this->admin)->get('/payments')->assertStatus(200);
        $this->actingAs($this->manager)->get('/payments')->assertStatus(200);
        $this->actingAs($this->coordinator)->get('/payments')->assertStatus(200);

        $this->actingAs($this->sales)->get('/payments')->assertStatus(403);
    }

    public function test_sidebar_displays_payments_link_for_authorized_roles_and_hides_for_sales(): void
    {
        $response = $this->actingAs($this->admin)->get('/projects');
        $response->assertSee('Payments');

        $responseCoord = $this->actingAs($this->coordinator)->get('/projects');
        $responseCoord->assertSee('Payments');

        $responseSales = $this->actingAs($this->sales)->get('/projects');
        $responseSales->assertDontSee(route('payments.index'));
    }

    public function test_partial_payment_logging_with_mode_reference_and_tds(): void
    {
        Livewire::actingAs($this->coordinator)
            ->test(PaymentModal::class)
            ->call('openPaymentModal', $this->project->id, $this->term->id)
            ->assertSet('project_id', $this->project->id)
            ->assertSet('term_id', $this->term->id)
            ->set('amount', 25000.00)
            ->set('tds', 1500.00)
            ->set('mode', 'NEFT')
            ->set('reference', 'NEFT987654321')
            ->set('bank', 'HDFC Bank')
            ->set('note', 'Initial tranche received')
            ->call('save')
            ->assertDispatched('payment-saved')
            ->assertDispatched('close-modal', 'payment-modal');

        $this->assertDatabaseHas('payments', [
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'term_id' => $this->term->id,
            'amount' => 25000.00,
            'tds' => 1500.00,
            'mode' => 'NEFT',
            'reference' => 'NEFT987654321',
            'bank' => 'HDFC Bank',
        ]);

        $this->term->refresh();
        $this->assertEquals(25000.00, (float) $this->term->received);
        $this->assertEquals(15000.00, (float) ($this->term->amount - $this->term->received));
        $this->assertEquals('Partial', $this->term->status);
    }

    public function test_full_payment_logging_marks_term_as_paid(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PaymentModal::class)
            ->call('openPaymentModal', $this->project->id, $this->term->id)
            ->set('amount', 38000.00)
            ->set('tds', 2000.00) // 38000 + 2000 = 40000 (total term amount)
            ->set('mode', 'RTGS')
            ->set('reference', 'RTGS112233')
            ->set('bank', 'ICICI Bank')
            ->call('save')
            ->assertDispatched('payment-saved');

        $this->term->refresh();
        $this->assertEquals(38000.00, (float) $this->term->received);
        $this->assertEquals(2000.00, (float) $this->term->tds);
        $this->assertEquals('Paid', $this->term->status);
    }

    public function test_smart_document_uploads_receipt_and_links_to_payment_and_documents(): void
    {
        $file = UploadedFile::fake()->create('bank_advice.pdf', 250, 'application/pdf');

        Livewire::actingAs($this->coordinator)
            ->test(PaymentModal::class)
            ->call('openPaymentModal', $this->project->id, $this->term->id)
            ->set('amount', 40000.00)
            ->set('tds', 0.0)
            ->set('mode', 'NEFT')
            ->set('reference', 'NEFT-ATTACH-001')
            ->set('receipt_file', $file)
            ->call('save')
            ->assertDispatched('payment-saved');

        $payment = Payment::where('reference', 'NEFT-ATTACH-001')->first();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->attachment);
        $this->assertNotNull($payment->document_id);

        $document = Document::find($payment->document_id);
        $this->assertNotNull($document);
        $this->assertEquals($this->customer->id, $document->customer_id);
        $this->assertEquals($this->project->id, $document->project_id);
        $this->assertEquals('bank_advice.pdf', $document->file_name);
        $this->assertEquals($payment->attachment, $document->stored_as);

        Storage::disk('uploads')->assertExists($payment->attachment);
        Storage::disk('public')->assertExists($payment->attachment);
    }

    public function test_smart_followup_milestone_chase_modal_persists_notes_and_followup(): void
    {
        $chaseDate = now()->addDays(2)->toDateString();
        $note = 'Client confirmed invoice passed to finance; settlement expected Wednesday.';

        Livewire::actingAs($this->coordinator)
            ->test(MilestoneChaseModal::class)
            ->call('openModal', $this->milestone->id)
            ->assertSet('milestoneId', $this->milestone->id)
            ->set('chase_notes', $note)
            ->set('next_chase_date', $chaseDate)
            ->call('save')
            ->assertDispatched('project-saved')
            ->assertDispatched('close-modal', 'milestone-chase-modal');

        $this->milestone->refresh();
        $this->assertEquals($note, $this->milestone->chase_notes);
        $this->assertEquals($chaseDate, $this->milestone->next_chase_date?->toDateString());

        $this->assertDatabaseHas('followups', [
            'customer_id' => $this->customer->id,
            'project_id' => $this->project->id,
            'type' => 'Note',
            'detail' => $note,
        ]);
        $followup = Followup::where('project_id', $this->project->id)->latest('id')->first();
        $this->assertNotNull($followup);
        $this->assertEquals($chaseDate, $followup->next_date ? Carbon::parse($followup->next_date)->toDateString() : null);
    }

    public function test_sales_user_is_forbidden_from_saving_payment(): void
    {
        Livewire::actingAs($this->sales)
            ->test(PaymentModal::class)
            ->call('openPaymentModal', $this->project->id, $this->term->id)
            ->set('amount', 10000.00)
            ->set('mode', 'Cash')
            ->call('save')
            ->assertForbidden();
    }

    public function test_payment_deletion_recalculates_term_balance_and_status(): void
    {
        $payment = Payment::create([
            'code' => 'RCP-TEST-DEL',
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'term_id' => $this->term->id,
            'amount' => 40000.00,
            'tds' => 0.00,
            'mode' => 'NEFT',
            'reference' => 'NEFT-DEL-001',
            'paid_on' => now()->toDateString(),
            'created_by' => $this->coordinator->id,
        ]);

        $this->term->update([
            'received' => 40000.00,
            'status' => 'Paid',
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaymentIndex::class)
            ->call('deletePayment', $payment->id)
            ->assertDispatched('payment-deleted');

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);

        $this->term->refresh();
        $this->assertEquals(0.00, (float) $this->term->received);
        $this->assertEquals('Pending', $this->term->status);
    }
}
