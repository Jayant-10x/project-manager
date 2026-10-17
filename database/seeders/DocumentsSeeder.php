<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DocumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'Admin')->first() ?? User::first();
        $coordinators = User::where('role', 'Project Coordinator')->get()->keyBy('name');

        $aakash = $coordinators['Aakash Verma'] ?? $admin;
        $sneha = $coordinators['Sneha Kulkarni'] ?? $admin;
        $rohan = $coordinators['Rohan Mehta'] ?? $admin;

        // Ensure directories exist
        $publicUploads = public_path('uploads');
        if (! File::isDirectory($publicUploads)) {
            File::makeDirectory($publicUploads, 0755, true);
        }

        $publicStorage = storage_path('app/public');
        if (! File::isDirectory($publicStorage)) {
            File::makeDirectory($publicStorage, 0755, true);
        }

        $c1 = Customer::where('customer_code', 'CUS-0001')->first();
        $c2 = Customer::where('customer_code', 'CUS-0002')->first();
        $c3 = Customer::where('customer_code', 'CUS-0003')->first();
        $c4 = Customer::where('customer_code', 'CUS-0004')->first();
        $c5 = Customer::where('customer_code', 'CUS-0005')->first();
        $c7 = Customer::where('customer_code', 'CUS-0007')->first();

        $p1 = Project::where('project_code', 'PJT-0001')->first();
        $p2 = Project::where('project_code', 'PJT-0002')->first();
        $p3 = Project::where('project_code', 'PJT-0003')->first();
        $p4 = Project::where('project_code', 'PJT-0004')->first();
        $p5 = Project::where('project_code', 'PJT-0005')->first();
        $p7 = Project::where('project_code', 'PJT-0007')->first();

        $documents = [
            // PJT-0001: Contract
            [
                'customer_id' => $c1?->id,
                'project_id' => $p1?->id,
                'category' => 'Contract',
                'title' => 'Master Services Agreement & SLA',
                'file_name' => 'techcorp-msa-signed-2026.pdf',
                'stored_as' => 'techcorp-msa-signed-2026_sample.pdf',
                'mime' => 'application/pdf',
                'size_kb' => 245,
                'uploaded_by' => $aakash->id,
                'content' => "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000010 00000 n\n0000000056 00000 n\n0000000111 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n190\n%%EOF",
            ],
            // PJT-0001: Delivery Proof
            [
                'customer_id' => $c1?->id,
                'project_id' => $p1?->id,
                'category' => 'Delivery Proof',
                'title' => 'Phase 1 Driver App Architecture Signoff',
                'file_name' => 'phase1-signoff-techcorp.pdf',
                'stored_as' => 'phase1-signoff-techcorp_sample.pdf',
                'mime' => 'application/pdf',
                'size_kb' => 180,
                'uploaded_by' => $aakash->id,
                'content' => "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000010 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n150\n%%EOF",
            ],
            // PJT-0002: KYC / GST
            [
                'customer_id' => $c2?->id,
                'project_id' => $p2?->id,
                'category' => 'KYC/GST',
                'title' => 'Apex Healthcare GST Registration Certificate',
                'file_name' => 'apex-gst-registration.pdf',
                'stored_as' => 'apex-gst-registration_sample.pdf',
                'mime' => 'application/pdf',
                'size_kb' => 320,
                'uploaded_by' => $sneha->id,
                'content' => "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000010 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n150\n%%EOF",
            ],
            // PJT-0002: Invoice
            [
                'customer_id' => $c2?->id,
                'project_id' => $p2?->id,
                'category' => 'Invoice',
                'title' => 'Proforma Tax Invoice INV-2026-0042',
                'file_name' => 'inv-2026-0042-telemed.pdf',
                'stored_as' => 'inv-2026-0042-telemed_sample.pdf',
                'mime' => 'application/pdf',
                'size_kb' => 140,
                'uploaded_by' => $sneha->id,
                'content' => "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000010 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n150\n%%EOF",
            ],
            // PJT-0003: Proposal
            [
                'customer_id' => $c3?->id,
                'project_id' => $p3?->id,
                'category' => 'Proposal',
                'title' => 'E-Commerce Omnichannel Technical Scope Document',
                'file_name' => 'bharat-retail-scope-proposal.docx',
                'stored_as' => 'bharat-retail-scope-proposal_sample.docx',
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'size_kb' => 512,
                'uploaded_by' => $rohan->id,
                'content' => 'Sample Docx Binary Data for Scope Proposal',
            ],
            // CUS-0004: Quotation
            [
                'customer_id' => $c4?->id,
                'project_id' => $p4?->id,
                'category' => 'Quotation',
                'title' => 'FinTech Engineering Sprint Rate Card & Quote',
                'file_name' => 'zeta-fintech-ratecard-q1.xlsx',
                'stored_as' => 'zeta-fintech-ratecard-q1_sample.xlsx',
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'size_kb' => 84,
                'uploaded_by' => $aakash->id,
                'content' => 'Sample XLSX Spreadsheet Content',
            ],
            // PJT-0005: AMC
            [
                'customer_id' => $c5?->id,
                'project_id' => $p5?->id,
                'category' => 'AMC',
                'title' => 'Annual Maintenance Contract Renewal (2026-2027)',
                'file_name' => 'nimbus-amc-contract-2026.pdf',
                'stored_as' => 'nimbus-amc-contract-2026_sample.pdf',
                'mime' => 'application/pdf',
                'size_kb' => 195,
                'uploaded_by' => $aakash->id,
                'content' => "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000010 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n150\n%%EOF",
            ],
            // PJT-0007: Other
            [
                'customer_id' => $c7?->id,
                'project_id' => $p7?->id,
                'category' => 'Other',
                'title' => 'IoT Gateway Hardware Payload Schema & Data Dictionary',
                'file_name' => 'biopure-iot-data-dictionary.csv',
                'stored_as' => 'biopure-iot-data-dictionary_sample.csv',
                'mime' => 'text/csv',
                'size_kb' => 15,
                'uploaded_by' => $rohan->id,
                'content' => "field_name,data_type,unit,description\nsensor_id,string,,Unique MAC address of telemetry node\nph_level,float,pH,Current fluid acidity/alkalinity\ntemp_celsius,float,C,Ambient chamber temperature\ntimestamp,integer,epoch,Unix milliseconds timestamp\n",
            ],
        ];

        foreach ($documents as $docData) {
            $content = $docData['content'];
            unset($docData['content']);

            if (! empty($docData['customer_id'])) {
                // Write physical mock file to both public/uploads and storage/app/public
                file_put_contents(public_path('uploads/'.$docData['stored_as']), $content);
                file_put_contents(storage_path('app/public/'.$docData['stored_as']), $content);

                Document::updateOrCreate(
                    [
                        'customer_id' => $docData['customer_id'],
                        'title' => $docData['title'],
                    ],
                    $docData
                );
            }
        }
    }
}
