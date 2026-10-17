<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salesReps = User::where('role', 'Sales')->get();
        $admin = User::where('role', 'Admin')->first();

        $repArjun = $salesReps->firstWhere('email', 'sales.arjun@the10xagency.in') ?? $salesReps->first();
        $repKavita = $salesReps->firstWhere('email', 'sales.kavita@the10xagency.in') ?? $salesReps->skip(1)->first() ?? $repArjun;
        $repSameer = $salesReps->firstWhere('email', 'sales.sameer@the10xagency.in') ?? $salesReps->skip(2)->first() ?? $repArjun;
        $repAnanya = $salesReps->firstWhere('email', 'sales.ananya@the10xagency.in') ?? $salesReps->skip(3)->first() ?? $repArjun;

        $customers = [
            [
                'customer_code' => 'CUS-0001',
                'company' => 'Apex Global Logistics Pvt Ltd',
                'contact_name' => 'Rajesh Khanna',
                'email' => 'rajesh.k@apexlogistics.in',
                'phone' => '9820011223',
                'alt_phone' => '02228394011',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'address' => 'Floor 4, Logistics Park, Andheri East, Mumbai 400069',
                'gst_number' => '27AAACA1234A1Z1',
                'pan_number' => 'AAACA1234A',
                'billing_name' => 'Apex Global Logistics Private Limited',
                'billing_address' => 'Floor 4, Logistics Park, Andheri East, Mumbai 400069',
                'source' => 'Inbound Web',
                'industry' => 'Logistics & Supply Chain',
                'status' => 'Active',
                'internal_notes' => 'Key enterprise account. Multi-year development retainer customer.',
                'sales_person_id' => $repArjun?->id,
                'created_by' => $admin?->id,
            ],
            [
                'customer_code' => 'CUS-0002',
                'company' => 'Bluecrest HealthTech Solutions',
                'contact_name' => 'Dr. Sunita Deshmukh',
                'email' => 'sunita.d@bluecresthealth.com',
                'phone' => '9845012345',
                'alt_phone' => '08022334455',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'address' => 'Tech Arcade, Outer Ring Road, Bellandur, Bengaluru 560103',
                'gst_number' => '29AAACB5678B1Z2',
                'pan_number' => 'AAACB5678B',
                'billing_name' => 'Bluecrest HealthTech Solutions LLP',
                'billing_address' => 'Tech Arcade, Outer Ring Road, Bellandur, Bengaluru 560103',
                'source' => 'Referral',
                'industry' => 'Healthcare & Telemedicine',
                'status' => 'Active',
                'internal_notes' => 'HIPAA compliant patient telemetry system and web platform.',
                'sales_person_id' => $repKavita?->id,
                'created_by' => $admin?->id,
            ],
            [
                'customer_code' => 'CUS-0003',
                'company' => 'Zenith Retail E-Commerce LLP',
                'contact_name' => 'Manish Agarwal',
                'email' => 'manish@zenithretail.in',
                'phone' => '9811098765',
                'alt_phone' => '01123456789',
                'city' => 'Delhi',
                'state' => 'Delhi',
                'address' => 'D-14 Connaught Place, Inner Circle, New Delhi 110001',
                'gst_number' => '07AAACZ9012C1Z3',
                'pan_number' => 'AAACZ9012C',
                'billing_name' => 'Zenith Retail E-Commerce LLP',
                'billing_address' => 'D-14 Connaught Place, Inner Circle, New Delhi 110001',
                'source' => 'Cold Outreach',
                'industry' => 'Fashion & Consumer Retail',
                'status' => 'Active',
                'internal_notes' => 'High volume Shopify Plus custom app integration and marketing funnel.',
                'sales_person_id' => $repSameer?->id,
                'created_by' => $admin?->id,
            ],
            [
                'customer_code' => 'CUS-0004',
                'company' => 'Optima Financial Advisory Services',
                'contact_name' => 'Deepak Suri',
                'email' => 'deepak@optimafin.com',
                'phone' => '9848023456',
                'alt_phone' => '04066778899',
                'city' => 'Hyderabad',
                'state' => 'Telangana',
                'address' => 'Cyber Towers, Hitec City, Madhapur, Hyderabad 500081',
                'gst_number' => '36AAACD3456D1Z4',
                'pan_number' => 'AAACD3456D',
                'billing_name' => 'Optima Financial Advisory Services Pvt Ltd',
                'billing_address' => 'Cyber Towers, Hitec City, Madhapur, Hyderabad 500081',
                'source' => 'LinkedIn Lead Gen',
                'industry' => 'Fintech & Investment Advisory',
                'status' => 'Active',
                'internal_notes' => 'Portfolio analytics dashboard and client investor portal.',
                'sales_person_id' => $repAnanya?->id,
                'created_by' => $admin?->id,
            ],
            [
                'customer_code' => 'CUS-0005',
                'company' => 'Eldorado Luxury Living Spaces',
                'contact_name' => 'Vikram Goel',
                'email' => 'vikram@eldoradospaces.in',
                'phone' => '9822034567',
                'alt_phone' => '02025678901',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'address' => 'Skyline Heights, Senapati Bapat Road, Pune 411016',
                'gst_number' => '27AAACE7890E1Z5',
                'pan_number' => 'AAACE7890E',
                'billing_name' => 'Eldorado Living Spaces Pvt Ltd',
                'billing_address' => 'Skyline Heights, Senapati Bapat Road, Pune 411016',
                'source' => 'Networking Event',
                'industry' => 'Real Estate & Infrastructure',
                'status' => 'On Hold',
                'internal_notes' => 'Phase 1 delivered. Phase 2 3D virtual walkthrough pending client architectural sign-off.',
                'sales_person_id' => $repArjun?->id,
                'created_by' => $admin?->id,
            ],
            [
                'customer_code' => 'CUS-0006',
                'company' => 'Starlight Edutech Academy',
                'contact_name' => 'Priyanka Sen',
                'email' => 'priyanka@starlightacademy.org',
                'phone' => '9830045678',
                'alt_phone' => '03322113344',
                'city' => 'Kolkata',
                'state' => 'West Bengal',
                'address' => 'Salt Lake Sector V, Bidhannagar, Kolkata 700091',
                'gst_number' => '19AAACS2345S1Z6',
                'pan_number' => 'AAACS2345S',
                'billing_name' => 'Starlight Edutech Academy Trust',
                'billing_address' => 'Salt Lake Sector V, Bidhannagar, Kolkata 700091',
                'source' => 'Google Search',
                'industry' => 'Education & E-Learning',
                'status' => 'Completed',
                'internal_notes' => 'LMS portal built and successfully deployed on AWS.',
                'sales_person_id' => $repKavita?->id,
                'created_by' => $admin?->id,
            ],
            [
                'customer_code' => 'CUS-0007',
                'company' => 'Kaveri Organic Agro Exports',
                'contact_name' => 'Sridhar Raman',
                'email' => 'sridhar@kaveriagro.com',
                'phone' => '9840056789',
                'alt_phone' => '04428114455',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'address' => 'Mount Road, Guindy Industrial Estate, Chennai 600032',
                'gst_number' => '33AAACK6789K1Z7',
                'pan_number' => 'AAACK6789K',
                'billing_name' => 'Kaveri Organic Agro Exports Pvt Ltd',
                'billing_address' => 'Mount Road, Guindy Industrial Estate, Chennai 600032',
                'source' => 'Direct Walk-in',
                'industry' => 'Agriculture & Export Trading',
                'status' => 'Churned',
                'internal_notes' => 'Contract terminated mutually due to client budget realignment.',
                'sales_person_id' => $repSameer?->id,
                'created_by' => $admin?->id,
            ],
        ];

        foreach ($customers as $cData) {
            Customer::updateOrCreate(
                ['customer_code' => $cData['customer_code']],
                $cData
            );
        }
    }
}
