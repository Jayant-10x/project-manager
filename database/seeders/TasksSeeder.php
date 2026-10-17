<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TasksSeeder extends Seeder
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

        $p1 = Project::where('project_code', 'PJT-0001')->first();
        $p2 = Project::where('project_code', 'PJT-0002')->first();
        $p3 = Project::where('project_code', 'PJT-0003')->first();
        $p4 = Project::where('project_code', 'PJT-0004')->first();
        $p5 = Project::where('project_code', 'PJT-0005')->first();
        $p7 = Project::where('project_code', 'PJT-0007')->first();

        $c1 = Customer::where('customer_code', 'CUS-0001')->first();
        $c2 = Customer::where('customer_code', 'CUS-0002')->first();
        $c3 = Customer::where('customer_code', 'CUS-0003')->first();
        $c5 = Customer::where('customer_code', 'CUS-0005')->first();

        // Tasks with Low, Medium, High, Urgent priorities, both done=false and done=true
        $tasks = [
            // PJT-0001
            [
                'title' => 'Configure Google Maps Fleet Engine API Keys in Staging',
                'project_id' => $p1?->id,
                'customer_id' => $c1?->id,
                'priority' => 'High',
                'due_date' => now()->addDays(2)->toDateString(),
                'done' => false,
                'assigned_to' => $aakash->id,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Submit Milestone 2 Proforma Invoice to Client Accounts',
                'project_id' => $p1?->id,
                'customer_id' => $c1?->id,
                'priority' => 'Medium',
                'due_date' => now()->addDays(4)->toDateString(),
                'done' => false,
                'assigned_to' => $aakash->id,
                'created_by' => $aakash->id,
            ],
            [
                'title' => 'Architecture Sign-off on Driver App WebSocket Latency',
                'project_id' => $p1?->id,
                'customer_id' => $c1?->id,
                'priority' => 'Low',
                'due_date' => now()->subDays(10)->toDateString(),
                'done' => true,
                'assigned_to' => $aakash->id,
                'created_by' => $admin->id,
            ],

            // PJT-0002
            [
                'title' => 'Escalate Overdue Term 2 Payment with Apex Finance Team',
                'project_id' => $p2?->id,
                'customer_id' => $c2?->id,
                'priority' => 'Urgent',
                'due_date' => now()->toDateString(),
                'done' => false,
                'assigned_to' => $sneha->id,
                'created_by' => $sneha->id,
            ],
            [
                'title' => 'Run Automated HIPAA Audit Trail Test Suite on Telehealth API',
                'project_id' => $p2?->id,
                'customer_id' => $c2?->id,
                'priority' => 'High',
                'due_date' => now()->addDays(3)->toDateString(),
                'done' => false,
                'assigned_to' => $sneha->id,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Verify WebRTC Fallback STUN/TURN Server Failovers',
                'project_id' => $p2?->id,
                'customer_id' => $c2?->id,
                'priority' => 'Medium',
                'due_date' => now()->subDays(5)->toDateString(),
                'done' => true,
                'assigned_to' => $sneha->id,
                'created_by' => $sneha->id,
            ],

            // PJT-0003
            [
                'title' => 'Resolve SAP Inventory Sync Timeout with Bharat Retail IT Team',
                'project_id' => $p3?->id,
                'customer_id' => $c3?->id,
                'priority' => 'Urgent',
                'due_date' => now()->addDay()->toDateString(),
                'done' => false,
                'assigned_to' => $rohan->id,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Follow up on Overdue Terms 2 & 3 Clearing UTR Numbers',
                'project_id' => $p3?->id,
                'customer_id' => $c3?->id,
                'priority' => 'High',
                'due_date' => now()->addDays(2)->toDateString(),
                'done' => false,
                'assigned_to' => $rohan->id,
                'created_by' => $rohan->id,
            ],

            // PJT-0004
            [
                'title' => 'Prepare Sprint Retainer Timesheet Report for Zeta FinTech',
                'project_id' => $p4?->id,
                'customer_id' => $p4?->customer_id,
                'priority' => 'Medium',
                'due_date' => now()->addDays(5)->toDateString(),
                'done' => false,
                'assigned_to' => $aakash->id,
                'created_by' => $aakash->id,
            ],

            // PJT-0005
            [
                'title' => 'Issue AMC Renewal Contract & Annual Invoice to Nimbus Cloud',
                'project_id' => $p5?->id,
                'customer_id' => $c5?->id,
                'priority' => 'High',
                'due_date' => now()->addDays(6)->toDateString(),
                'done' => false,
                'assigned_to' => $aakash->id,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Archive Final Codebase & Source Control Snapshot to Cold Storage',
                'project_id' => $p5?->id,
                'customer_id' => $c5?->id,
                'priority' => 'Low',
                'due_date' => now()->subDays(15)->toDateString(),
                'done' => true,
                'assigned_to' => $aakash->id,
                'created_by' => $admin->id,
            ],

            // PJT-0007
            [
                'title' => 'Complete Sensor Payload Decoder Integration Specs',
                'project_id' => $p7?->id,
                'customer_id' => $p7?->customer_id,
                'priority' => 'Medium',
                'due_date' => now()->addDays(7)->toDateString(),
                'done' => false,
                'assigned_to' => $rohan->id,
                'created_by' => $rohan->id,
            ],

            // General / Agency tasks without a project
            [
                'title' => 'Review Monthly Project Health Dashboard with Directors',
                'project_id' => null,
                'customer_id' => null,
                'priority' => 'High',
                'due_date' => now()->addDays(3)->toDateString(),
                'done' => false,
                'assigned_to' => $admin->id,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Run Database Backup Verification and Off-site S3 Replication Audit',
                'project_id' => null,
                'customer_id' => null,
                'priority' => 'Medium',
                'due_date' => now()->subDays(2)->toDateString(),
                'done' => true,
                'assigned_to' => $admin->id,
                'created_by' => $admin->id,
            ],
        ];

        foreach ($tasks as $taskData) {
            Task::updateOrCreate(
                [
                    'title' => $taskData['title'],
                    'assigned_to' => $taskData['assigned_to'],
                ],
                $taskData
            );
        }
    }
}
