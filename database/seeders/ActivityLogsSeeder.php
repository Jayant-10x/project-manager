<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ActivityLogsSeeder extends Seeder
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
        $c1 = Customer::where('customer_code', 'CUS-0001')->first();
        $rcp1 = Payment::where('code', 'RCP-0001')->first();
        $doc1 = Document::where('file_name', 'techcorp-msa-signed-2026.pdf')->first();

        $logs = [
            [
                'user_id' => $admin->id,
                'action' => 'User Login',
                'entity' => 'User',
                'entity_id' => $admin->id,
                'entity_code' => 'USR-0001',
                'detail' => 'User authenticated successfully from session 127.0.0.1.',
                'at_time' => Carbon::now()->subHours(2),
            ],
            [
                'user_id' => $aakash->id,
                'action' => 'Created Project',
                'entity' => 'Project',
                'entity_id' => $p1?->id,
                'entity_code' => $p1?->project_code ?? 'PJT-0001',
                'detail' => "Created project 'TechCorp Logistics Portal' for TechCorp Logistics Ltd.",
                'at_time' => Carbon::now()->subMonths(3),
            ],
            [
                'user_id' => $aakash->id,
                'action' => 'Created Payment',
                'entity' => 'Payment',
                'entity_id' => $rcp1?->id,
                'entity_code' => 'RCP-0001',
                'detail' => 'Recorded receipt of ₹229,500 via RTGS for TechCorp Logistics Portal (TDS: ₹25,500).',
                'at_time' => Carbon::now()->subMonths(3),
            ],
            [
                'user_id' => $aakash->id,
                'action' => 'Uploaded Document',
                'entity' => 'Document',
                'entity_id' => $doc1?->id,
                'entity_code' => 'DOC',
                'detail' => 'Uploaded Master Services Agreement & SLA (techcorp-msa-signed-2026.pdf).',
                'at_time' => Carbon::now()->subMonths(2)->subDays(15),
            ],
            [
                'user_id' => $sneha->id,
                'action' => 'Updated Milestone',
                'entity' => 'Milestone',
                'entity_id' => $p2?->id,
                'entity_code' => 'MLS-02',
                'detail' => 'Marked milestone Patient EHR & Video Consultation Suite as completed.',
                'at_time' => Carbon::now()->subDays(15),
            ],
            [
                'user_id' => $rohan->id,
                'action' => 'Logged Followup',
                'entity' => 'Followup',
                'entity_id' => $p2?->id,
                'entity_code' => 'CRM',
                'detail' => 'Logged WhatsApp interaction regarding SAP staging unblock.',
                'at_time' => Carbon::now()->subDay(),
            ],
            [
                'user_id' => $admin->id,
                'action' => 'Updated Settings',
                'entity' => 'Setting',
                'entity_id' => null,
                'entity_code' => 'CONF',
                'detail' => 'Updated agency profile, GSTIN tax configuration, and alert thresholds.',
                'at_time' => Carbon::now()->subDays(5),
            ],
        ];

        foreach ($logs as $logData) {
            ActivityLog::updateOrCreate(
                [
                    'user_id' => $logData['user_id'],
                    'action' => $logData['action'],
                    'at_time' => $logData['at_time'],
                ],
                $logData
            );
        }
    }
}
