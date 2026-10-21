<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Seeder;

class ProjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coordinators = User::where('role', 'Coordinator')->get();
        $coord1 = $coordinators->firstWhere('email', 'coordinator1@the10xagency.in') ?? $coordinators->first();
        $coord2 = $coordinators->firstWhere('email', 'coordinator2@the10xagency.in') ?? $coordinators->skip(1)->first() ?? $coord1;
        $coord3 = $coordinators->firstWhere('email', 'coordinator3@the10xagency.in') ?? $coordinators->skip(2)->first() ?? $coord1;

        $salesReps = User::where('role', 'Sales')->get();
        $repArjun = $salesReps->firstWhere('email', 'sales.arjun@the10xagency.in') ?? $salesReps->first();
        $repKavita = $salesReps->firstWhere('email', 'sales.kavita@the10xagency.in') ?? $salesReps->skip(1)->first() ?? $repArjun;
        $repSameer = $salesReps->firstWhere('email', 'sales.sameer@the10xagency.in') ?? $salesReps->skip(2)->first() ?? $repArjun;
        $repAnanya = $salesReps->firstWhere('email', 'sales.ananya@the10xagency.in') ?? $salesReps->skip(3)->first() ?? $repArjun;

        $admin = User::where('role', 'Admin')->first();

        $cus1 = Customer::where('customer_code', 'CUS-0001')->first();
        $cus2 = Customer::where('customer_code', 'CUS-0002')->first();
        $cus3 = Customer::where('customer_code', 'CUS-0003')->first();
        $cus4 = Customer::where('customer_code', 'CUS-0004')->first();
        $cus5 = Customer::where('customer_code', 'CUS-0005')->first();
        $cus6 = Customer::where('customer_code', 'CUS-0006')->first();
        $cus7 = Customer::where('customer_code', 'CUS-0007')->first();

        $projects = [
            // PJT-0001: In Progress, On Track, AMC Required (Active AMC)
            [
                'project_code' => 'PJT-0001',
                'customer_id' => $cus1?->id,
                'name' => 'Fleet Track & Dispatch Telematics Platform',
                'description' => 'Real-time GPS tracking, driver dispatch app, and route optimization engine.',
                'project_type' => 'Custom Web & Mobile App',
                'value' => 850000.00,
                'cost' => 380000.00,
                'start_date' => now()->subMonths(3)->toDateString(),
                'delivery_date' => now()->addMonths(2)->toDateString(),
                'status' => 'In Progress',
                'health' => 'On Track',
                'delay_reason' => null,
                'amc_required' => true,
                'amc_start' => now()->addMonths(2)->toDateString(),
                'amc_end' => now()->addMonths(14)->toDateString(),
                'amc_amount' => 120000.00,
                'renew_alert_days' => 15,
                'handover_done' => false,
                'coordinator_id' => $coord1?->id,
                'sales_person_id' => $repArjun?->id,
                'created_by' => $admin?->id,
            ],
            // PJT-0002: In Progress, At Risk (Approaching milestone deadline)
            [
                'project_code' => 'PJT-0002',
                'customer_id' => $cus2?->id,
                'name' => 'Patient Vital EHR Telemedicine Suite',
                'description' => 'EHR database, WebRTC video consultation, and automated prescription engine.',
                'project_type' => 'Healthcare Web Portal',
                'value' => 1250000.00,
                'cost' => 600000.00,
                'start_date' => now()->subMonths(4)->toDateString(),
                'delivery_date' => now()->addDays(10)->toDateString(),
                'status' => 'In Progress',
                'health' => 'At Risk',
                'delay_reason' => 'Third-party SMS OTP & Aadhaar verification API integration delay.',
                'amc_required' => true,
                'amc_start' => now()->toDateString(),
                'amc_end' => now()->addDays(5)->toDateString(), // AMC renewal alert triggered!
                'amc_amount' => 180000.00,
                'renew_alert_days' => 7,
                'handover_done' => false,
                'coordinator_id' => $coord2?->id,
                'sales_person_id' => $repKavita?->id,
                'created_by' => $admin?->id,
            ],
            // PJT-0003: In Progress, Delayed (Overdue delivery date)
            [
                'project_code' => 'PJT-0003',
                'customer_id' => $cus3?->id,
                'name' => 'Zenith Multi-Brand Omni-Channel Marketplace',
                'description' => 'Multi-vendor marketplace, warehouse sync, and payment gateway integration.',
                'project_type' => 'E-Commerce Platform',
                'value' => 650000.00,
                'cost' => 280000.00,
                'start_date' => now()->subMonths(5)->toDateString(),
                'delivery_date' => now()->subDays(12)->toDateString(), // Delayed!
                'status' => 'In Progress',
                'health' => 'Delayed',
                'delay_reason' => 'Client product catalog data cleansing and ERP sync delays.',
                'amc_required' => false,
                'amc_start' => null,
                'amc_end' => null,
                'amc_amount' => 0.00,
                'renew_alert_days' => 7,
                'handover_done' => false,
                'coordinator_id' => $coord3?->id,
                'sales_person_id' => $repSameer?->id,
                'created_by' => $admin?->id,
            ],
            // PJT-0004: Onboarding, On Track
            [
                'project_code' => 'PJT-0004',
                'customer_id' => $cus4?->id,
                'name' => 'Wealth Management Portfolio Engine',
                'description' => 'Robo-advisory algorithm, mutual fund API feeds, and investor app.',
                'project_type' => 'Fintech Mobile App',
                'value' => 950000.00,
                'cost' => 420000.00,
                'start_date' => now()->subDays(5)->toDateString(),
                'delivery_date' => now()->addMonths(4)->toDateString(),
                'status' => 'Onboarding',
                'health' => 'On Track',
                'delay_reason' => null,
                'amc_required' => true,
                'amc_start' => now()->addMonths(4)->toDateString(),
                'amc_end' => now()->addMonths(16)->toDateString(),
                'amc_amount' => 150000.00,
                'renew_alert_days' => 14,
                'handover_done' => false,
                'coordinator_id' => $coord1?->id,
                'sales_person_id' => $repAnanya?->id,
                'created_by' => $admin?->id,
            ],
            // PJT-0005: Delivered, Completed, Handover Pending
            [
                'project_code' => 'PJT-0005',
                'customer_id' => $cus5?->id,
                'name' => '3D Architectural Virtual Tour Experience',
                'description' => 'WebGL architectural models, apartment customizer, and broker sales dashboard.',
                'project_type' => '3D Interactive Web',
                'value' => 500000.00,
                'cost' => 210000.00,
                'start_date' => now()->subMonths(6)->toDateString(),
                'delivery_date' => now()->subMonths(1)->toDateString(),
                'actual_end' => now()->subDays(15)->toDateString(),
                'status' => 'Delivered',
                'health' => 'Completed',
                'delay_reason' => null,
                'amc_required' => false,
                'amc_start' => null,
                'amc_end' => null,
                'amc_amount' => 0.00,
                'renew_alert_days' => 7,
                'handover_done' => false, // Handover Pending!
                'handover_notes' => 'Awaiting client signature on final UAT sign-off certificate.',
                'coordinator_id' => $coord2?->id,
                'sales_person_id' => $repArjun?->id,
                'created_by' => $admin?->id,
            ],
            // PJT-0006: Delivered, Completed, Handover Completed
            [
                'project_code' => 'PJT-0006',
                'customer_id' => $cus6?->id,
                'name' => 'Cloud Learning Management System',
                'description' => 'Video streaming, automated quiz evaluation, and certificate generation.',
                'project_type' => 'EdTech SaaS',
                'value' => 750000.00,
                'cost' => 310000.00,
                'start_date' => now()->subMonths(8)->toDateString(),
                'delivery_date' => now()->subMonths(2)->toDateString(),
                'actual_end' => now()->subMonths(2)->toDateString(),
                'status' => 'Delivered',
                'health' => 'Completed',
                'delay_reason' => null,
                'amc_required' => true,
                'amc_start' => now()->subMonths(2)->toDateString(),
                'amc_end' => now()->addMonths(10)->toDateString(),
                'amc_amount' => 100000.00,
                'renew_alert_days' => 10,
                'handover_done' => true,
                'handover_notes' => 'Complete handover to internal Starlight IT engineering team.',
                'coordinator_id' => $coord3?->id,
                'sales_person_id' => $repKavita?->id,
                'created_by' => $admin?->id,
            ],
            // PJT-0007: On Hold, At Risk
            [
                'project_code' => 'PJT-0007',
                'customer_id' => $cus7?->id,
                'name' => 'Farm-to-Fork Traceability Blockchain App',
                'description' => 'QR code batch tracing, farmer cooperative portal, and export customs filing.',
                'project_type' => 'Supply Chain Platform',
                'value' => 600000.00,
                'cost' => 220000.00,
                'start_date' => now()->subMonths(4)->toDateString(),
                'delivery_date' => now()->addMonths(1)->toDateString(),
                'status' => 'On Hold',
                'health' => 'At Risk',
                'delay_reason' => 'Client restructuring export division; budget frozen temporarily.',
                'amc_required' => false,
                'coordinator_id' => $coord1?->id,
                'sales_person_id' => $repSameer?->id,
                'created_by' => $admin?->id,
            ],
            // PJT-0008: Cancelled
            [
                'project_code' => 'PJT-0008',
                'customer_id' => $cus7?->id,
                'name' => 'Legacy Warehouse Migration Tool',
                'description' => 'Batch file parser to convert legacy FoxPro warehouse database to PostgreSQL.',
                'project_type' => 'Data Migration Utility',
                'value' => 180000.00,
                'cost' => 75000.00,
                'start_date' => now()->subMonths(6)->toDateString(),
                'delivery_date' => now()->subMonths(3)->toDateString(),
                'status' => 'Cancelled',
                'health' => 'Delayed',
                'delay_reason' => 'Project superseded by enterprise ERP vendor implementation.',
                'amc_required' => false,
                'coordinator_id' => $coord2?->id,
                'sales_person_id' => $repSameer?->id,
                'created_by' => $admin?->id,
            ],
        ];

        foreach ($projects as $pData) {
            Project::updateOrCreate(
                ['project_code' => $pData['project_code']],
                $pData
            );
        }

        app(CodeGeneratorService::class)->sync('project');
    }
}
