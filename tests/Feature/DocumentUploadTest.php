<?php

namespace Tests\Feature;

use App\Livewire\Documents\DocumentUploadModal;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_upload_modal_accepts_valid_csv_file(): void
    {
        Storage::fake('public');
        Storage::fake('uploads');

        $user = User::factory()->create(['role' => 'Coordinator']);
        $customer = Customer::create([
            'customer_code' => 'CUS-001',
            'company' => 'Acme Corporation',
            'status' => 'Active',
        ]);
        $project = Project::create([
            'project_code' => 'PJT-001',
            'name' => 'CRM Portal',
            'customer_id' => $customer->id,
            'status' => 'In Progress',
            'health' => 'On Track',
            'value' => 50000,
        ]);

        $csvFile = UploadedFile::fake()->create('client_data.csv', 10, 'text/csv');

        Livewire::actingAs($user)
            ->test(DocumentUploadModal::class)
            ->call('openModal', $customer->id, $project->id)
            ->set('file', $csvFile)
            ->set('title', 'Client Data CSV')
            ->set('category', 'Contract')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('document-saved')
            ->assertDispatched('close-modal', 'document-upload-modal');

        $this->assertDatabaseHas('documents', [
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'title' => 'Client Data CSV',
            'file_name' => 'client_data.csv',
        ]);

        $doc = Document::where('file_name', 'client_data.csv')->first();
        $this->assertNotNull($doc);
        Storage::disk('public')->assertExists($doc->stored_as);
        Storage::disk('uploads')->assertExists($doc->stored_as);
        $this->assertMatchesRegularExpression('/^client-data_\d{8}_\d{6}\.csv$/', $doc->stored_as);

        // Test download route with original filename
        $response = $this->actingAs($user)->get(route('documents.download', $doc->id));
        $response->assertOk();
        $this->assertStringContainsString('filename=client_data.csv', $response->headers->get('content-disposition'));
    }

    public function test_document_download_returns_404_when_file_not_found(): void
    {
        Storage::fake('public');
        Storage::fake('uploads');

        $user = User::factory()->create(['role' => 'Coordinator']);
        $doc = Document::create([
            'title' => 'Missing File',
            'file_name' => 'missing.pdf',
            'stored_as' => 'nonexistent_file.pdf',
            'mime' => 'application/pdf',
            'size_kb' => 10,
            'uploaded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('documents.download', $doc->id));
        $response->assertNotFound();
    }

    public function test_document_upload_rejects_disallowed_file_types(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'Coordinator']);
        $customer = Customer::create([
            'customer_code' => 'CUS-002',
            'company' => 'Beta Global',
            'status' => 'Active',
        ]);

        $invalidFile = UploadedFile::fake()->create('malicious_script.exe', 50, 'application/x-msdownload');

        Livewire::actingAs($user)
            ->test(DocumentUploadModal::class)
            ->call('openModal', $customer->id)
            ->set('file', $invalidFile)
            ->set('title', 'Executable Test')
            ->call('save')
            ->assertHasErrors(['file']);
    }

    public function test_document_upload_rejects_files_exceeding_size_limit(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'Coordinator']);
        $customer = Customer::create([
            'customer_code' => 'CUS-003',
            'company' => 'Gamma Solutions',
            'status' => 'Active',
        ]);

        // 12 MB file (limit is 10 MB)
        $largeFile = UploadedFile::fake()->create('huge_contract.pdf', 12288, 'application/pdf');

        Livewire::actingAs($user)
            ->test(DocumentUploadModal::class)
            ->call('openModal', $customer->id)
            ->set('file', $largeFile)
            ->set('title', 'Huge PDF')
            ->call('save')
            ->assertHasErrors(['file']);
    }
}
