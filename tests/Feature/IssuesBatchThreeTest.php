<?php

namespace Tests\Feature;

use App\Livewire\Payments\PaymentModal;
use App\Livewire\Profile\ProfileDrawer;
use App\Mail\TemporaryPasswordMail;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Services\PasswordGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class IssuesBatchThreeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'Admin',
            'status' => 'Active',
        ]);
    }

    /**
     * ISS-091: Verify database migrations have zero alter migrations,
     * and customers and projects tables define approval_status in base create tables.
     */
    public function test_iss_091_no_alter_migrations_exist_and_base_schema_has_approval_status(): void
    {
        $migrationFiles = File::files(database_path('migrations'));
        foreach ($migrationFiles as $file) {
            $filename = $file->getFilename();
            $this->assertStringNotContainsString('modify', $filename, "Found alter migration: {$filename}");
            $this->assertStringNotContainsString('alter', $filename, "Found alter migration: {$filename}");
        }

        $this->assertTrue(Schema::hasColumn('customers', 'approval_status'));
        $this->assertTrue(Schema::hasColumn('projects', 'approval_status'));
    }

    /**
     * ISS-092: Verify TemporaryPasswordMail template contains no inert JavaScript buttons
     * or navigator.clipboard, and instead renders user-select: all credentials block and login link.
     */
    public function test_iss_092_temporary_password_email_view_is_client_safe_without_scripts(): void
    {
        $mailable = new TemporaryPasswordMail($this->admin, 'Xk9#mP2$qL5*vR8@', 'Admin');
        $html = $mailable->render();

        // Must not contain interactive JavaScript buttons or clipboard APIs
        $this->assertStringNotContainsString('navigator.clipboard', $html);
        $this->assertStringNotContainsString('onclick=', $html);
        $this->assertStringNotContainsString('<button', $html);

        // Must contain user-select: all and the temporary password
        $this->assertStringContainsString('user-select: all', $html);
        $this->assertStringContainsString('Xk9#mP2$qL5*vR8@', $html);
        $this->assertStringContainsString('Select and copy your temporary password above', $html);
        $this->assertStringContainsString(route('login'), $html);
    }

    /**
     * ISS-093: Verify ProfileDrawer enforces password complexity (min 8 chars, 1 uppercase,
     * 1 lowercase, 1 number, and different from current password) and invokes PasswordGeneratorService.
     */
    public function test_iss_093_profile_drawer_enforces_password_complexity(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProfileDrawer::class)
            ->dispatch('open-profile-drawer')
            ->set('showPasswordSection', true)
            ->set('current_password', 'Password123!')
            ->set('new_password', 'simplepass') // Missing uppercase and number
            ->set('new_password_confirmation', 'simplepass')
            ->call('save')
            ->assertHasErrors(['new_password']);

        // Password identical to current password must fail
        Livewire::actingAs($this->admin)
            ->test(ProfileDrawer::class)
            ->dispatch('open-profile-drawer')
            ->set('showPasswordSection', true)
            ->set('current_password', 'Password123!')
            ->set('new_password', 'Password123!')
            ->set('new_password_confirmation', 'Password123!')
            ->call('save')
            ->assertHasErrors(['new_password']);

        // Compliant password succeeds
        Livewire::actingAs($this->admin)
            ->test(ProfileDrawer::class)
            ->dispatch('open-profile-drawer')
            ->set('showPasswordSection', true)
            ->set('current_password', 'Password123!')
            ->set('new_password', 'NewCompliant@Pass2026')
            ->set('new_password_confirmation', 'NewCompliant@Pass2026')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('NewCompliant@Pass2026', $this->admin->fresh()->getAuthPassword()));
    }

    /**
     * ISS-093: Verify ProfileDrawer generateRandomPassword generates a 16-character
     * cryptographically secure compliant password.
     */
    public function test_iss_093_profile_drawer_uses_password_generator_service(): void
    {
        $comp = Livewire::actingAs($this->admin)
            ->test(ProfileDrawer::class)
            ->dispatch('open-profile-drawer')
            ->call('generateRandomPassword');

        $generated = $comp->get('new_password');
        $this->assertEquals(16, strlen($generated));
        $this->assertEquals($generated, $comp->get('new_password_confirmation'));
        $this->assertMatchesRegularExpression('/[A-Z]/', $generated);
        $this->assertMatchesRegularExpression('/[a-z]/', $generated);
        $this->assertMatchesRegularExpression('/[0-9]/', $generated);
    }

    /**
     * ISS-094/ISS-103: Verify PaymentModal persists uploaded receipt to canonical private documents disk.
     */
    public function test_iss_094_payment_modal_receipt_upload_replicates_to_public_storage(): void
    {
        Storage::fake('documents');

        $customer = Customer::create([
            'customer_code' => 'CUS-ISS-094',
            'company' => 'Storage Test Corp',
            'contact_name' => 'Storage Tester',
            'phone' => '9888877770',
            'state' => 'MH',
            'status' => 'Active',
            'approval_status' => 'Approved',
        ]);

        $project = Project::create([
            'project_code' => 'PJT-ISS-094',
            'name' => 'Storage Test Project',
            'customer_id' => $customer->id,
            'status' => 'In Progress',
            'approval_status' => 'Approved',
            'value' => 50000,
        ]);

        $fakePdf = UploadedFile::fake()->create('bank_receipt.pdf', 150, 'application/pdf');

        Livewire::actingAs($this->admin)
            ->test(PaymentModal::class)
            ->call('openModal')
            ->set('project_id', $project->id)
            ->set('amount', 25000)
            ->set('mode', 'NEFT')
            ->set('reference', 'UTR987654321')
            ->set('paid_on', now()->toDateString())
            ->set('receipt_file', $fakePdf)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('payment-saved');

        $payment = Payment::where('project_id', $project->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->attachment);

        // Verify stored in canonical documents disk
        Storage::disk('documents')->assertExists($payment->attachment);

        // Verify Document record created
        $document = Document::where('stored_as', $payment->attachment)->first();
        $this->assertNotNull($document);
        $this->assertEquals('bank_receipt.pdf', $document->file_name);
    }
}
