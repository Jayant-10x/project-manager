<?php

namespace Database\Seeders;

use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Database\Seeder;

class MilestonesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::all();

        $milestoneTemplates = [
            'PJT-0001' => [
                ['name' => 'Architecture & API Schema Sign-off', 'due_date' => now()->subMonths(2)->toDateString(), 'done' => true, 'done_on' => now()->subMonths(2)->toDateString(), 'status' => 'Done', 'sort_order' => 1],
                ['name' => 'Driver Mobile App (iOS & Android) Beta', 'due_date' => now()->subMonths(1)->toDateString(), 'done' => true, 'done_on' => now()->subMonths(1)->toDateString(), 'status' => 'Done', 'sort_order' => 2],
                ['name' => 'Dispatcher Real-time Web Dashboard', 'due_date' => now()->addDays(15)->toDateString(), 'done' => false, 'done_on' => null, 'status' => 'In Progress', 'sort_order' => 3],
                ['name' => 'Load Testing & End-to-End Simulation', 'due_date' => now()->addMonths(1)->toDateString(), 'done' => false, 'done_on' => null, 'status' => 'Pending', 'sort_order' => 4],
                ['name' => 'Production Deployment & Cutover', 'due_date' => now()->addMonths(2)->toDateString(), 'done' => false, 'done_on' => null, 'status' => 'Pending', 'sort_order' => 5],
            ],
            'PJT-0002' => [
                ['name' => 'HIPAA Security & Database Encryption Specs', 'due_date' => now()->subMonths(3)->toDateString(), 'done' => true, 'done_on' => now()->subMonths(3)->toDateString(), 'status' => 'Done', 'sort_order' => 1],
                ['name' => 'WebRTC Video Consultation Module', 'due_date' => now()->subMonths(1)->toDateString(), 'done' => true, 'done_on' => now()->subMonths(1)->toDateString(), 'status' => 'Done', 'sort_order' => 2],
                ['name' => 'Aadhaar & SMS OTP Verification Integration', 'due_date' => now()->subDays(5)->toDateString(), 'done' => false, 'done_on' => null, 'status' => 'Blocked', 'notes' => 'Awaiting sandbox credentials from NIC.', 'sort_order' => 3],
                ['name' => 'Doctor Clinic Management Portal', 'due_date' => now()->addDays(5)->toDateString(), 'done' => false, 'done_on' => null, 'status' => 'In Progress', 'sort_order' => 4],
                ['name' => 'Final UAT Sign-off', 'due_date' => now()->addDays(10)->toDateString(), 'done' => false, 'done_on' => null, 'status' => 'Pending', 'sort_order' => 5],
            ],
            'PJT-0003' => [
                ['name' => 'Shopify Plus Store Theme Customization', 'due_date' => now()->subMonths(4)->toDateString(), 'done' => true, 'done_on' => now()->subMonths(4)->toDateString(), 'status' => 'Done', 'sort_order' => 1],
                ['name' => 'Payment Gateway & Multi-Currency Checkout', 'due_date' => now()->subMonths(2)->toDateString(), 'done' => true, 'done_on' => now()->subMonths(2)->toDateString(), 'status' => 'Done', 'sort_order' => 2],
                ['name' => 'ERP & Warehouse Inventory Bi-directional Sync', 'due_date' => now()->subDays(20)->toDateString(), 'done' => false, 'done_on' => null, 'status' => 'In Progress', 'notes' => 'Field mapping mismatch between SAP and Shopify.', 'sort_order' => 3],
                ['name' => 'End-to-End Cart & Order Fulfillment Testing', 'due_date' => now()->subDays(5)->toDateString(), 'done' => false, 'done_on' => null, 'status' => 'Pending', 'sort_order' => 4],
                ['name' => 'Store Launch & Live Traffic Monitoring', 'due_date' => now()->addDays(10)->toDateString(), 'done' => false, 'done_on' => null, 'status' => 'Pending', 'sort_order' => 5],
            ],
            'PJT-0004' => [
                ['name' => 'Project Kickoff & Requirements Specification', 'due_date' => now()->addDays(5)->toDateString(), 'done' => false, 'done_on' => null, 'status' => 'In Progress', 'sort_order' => 1],
                ['name' => 'Interactive Figma UI/UX Prototypes', 'due_date' => now()->addDays(20)->toDateString(), 'done' => false, 'done_on' => null, 'status' => 'Pending', 'sort_order' => 2],
                ['name' => 'BSE/NSE Market Feed Gateway Integration', 'due_date' => now()->addMonths(2)->toDateString(), 'done' => false, 'done_on' => null, 'status' => 'Pending', 'sort_order' => 3],
            ],
            'PJT-0005' => [
                ['name' => '3D Architectural Model Clean-up', 'due_date' => now()->subMonths(4)->toDateString(), 'done' => true, 'done_on' => now()->subMonths(4)->toDateString(), 'status' => 'Done', 'sort_order' => 1],
                ['name' => 'Three.js Interactive Viewer Engine', 'due_date' => now()->subMonths(2)->toDateString(), 'done' => true, 'done_on' => now()->subMonths(2)->toDateString(), 'status' => 'Done', 'sort_order' => 2],
                ['name' => 'Broker Touchscreen Sales Kiosk Deployment', 'due_date' => now()->subDays(20)->toDateString(), 'done' => true, 'done_on' => now()->subDays(15)->toDateString(), 'status' => 'Done', 'sort_order' => 3],
            ],
            'PJT-0006' => [
                ['name' => 'AWS MediaConvert Video Pipeline Setup', 'due_date' => now()->subMonths(6)->toDateString(), 'done' => true, 'done_on' => now()->subMonths(6)->toDateString(), 'status' => 'Done', 'sort_order' => 1],
                ['name' => 'Student Dashboard & Quiz Engine', 'due_date' => now()->subMonths(4)->toDateString(), 'done' => true, 'done_on' => now()->subMonths(4)->toDateString(), 'status' => 'Done', 'sort_order' => 2],
                ['name' => 'Faculty Grading & Analytics Portal', 'due_date' => now()->subMonths(2)->toDateString(), 'done' => true, 'done_on' => now()->subMonths(2)->toDateString(), 'status' => 'Done', 'sort_order' => 3],
            ],
        ];

        foreach ($milestoneTemplates as $pCode => $mList) {
            $project = $projects->firstWhere('project_code', $pCode);
            if (! $project) {
                continue;
            }

            foreach ($mList as $mData) {
                Milestone::updateOrCreate(
                    [
                        'project_id' => $project->id,
                        'name' => $mData['name'],
                    ],
                    array_merge($mData, ['project_id' => $project->id])
                );
            }
        }
    }
}
