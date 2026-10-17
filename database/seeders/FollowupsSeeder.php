<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Followup;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class FollowupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'Admin')->first() ?? User::first();
        $salesReps = User::where('role', 'Sales Person')->get()->keyBy('name');
        $coordinators = User::where('role', 'Project Coordinator')->get()->keyBy('name');

        $arjun = $salesReps['Arjun Nair'] ?? $admin;
        $kavita = $salesReps['Kavita Rao'] ?? $admin;
        $sameer = $salesReps['Sameer Khan'] ?? $admin;
        $ananya = $salesReps['Ananya Joshi'] ?? $admin;

        $aakash = $coordinators['Aakash Verma'] ?? $admin;
        $sneha = $coordinators['Sneha Kulkarni'] ?? $admin;
        $rohan = $coordinators['Rohan Mehta'] ?? $admin;

        // Customer CUS-0001 (TechCorp)
        $c1 = Customer::where('customer_code', 'CUS-0001')->first();
        $p1 = Project::where('project_code', 'PJT-0001')->first();
        if ($c1) {
            Followup::updateOrCreate(
                [
                    'customer_id' => $c1->id,
                    'project_id' => $p1?->id,
                    'summary' => 'Weekly sprint review & dispatcher progress update',
                ],
                [
                    'type' => 'Meeting',
                    'detail' => 'Met with Vikram Sharma (VP Engineering). Demonstrated fleet tracking live GPS feeds and mobile push notification latency. Client very satisfied with sprint velocity.',
                    'happened_on' => now()->subDays(3)->toDateString(),
                    'next_date' => now()->addDays(4)->toDateString(),
                    'next_note' => 'Deliver Beta build APK and schedule UAT kickoff.',
                    'created_by' => $aakash->id,
                ]
            );

            Followup::updateOrCreate(
                [
                    'customer_id' => $c1->id,
                    'project_id' => $p1?->id,
                    'summary' => 'Commercial discussion on upcoming milestone installment',
                ],
                [
                    'type' => 'Call',
                    'detail' => 'Called finance director Ramesh to advise that Milestone 2 is approaching delivery. Invoiced will be submitted by Friday.',
                    'happened_on' => now()->subDays(8)->toDateString(),
                    'next_date' => now()->addDays(5)->toDateString(),
                    'next_note' => 'Confirm receipt of invoice and PO clearance.',
                    'created_by' => $arjun->id,
                ]
            );
        }

        // Customer CUS-0002 (Apex Healthcare)
        $c2 = Customer::where('customer_code', 'CUS-0002')->first();
        $p2 = Project::where('project_code', 'PJT-0002')->first();
        if ($c2) {
            Followup::updateOrCreate(
                [
                    'customer_id' => $c2->id,
                    'project_id' => $p2?->id,
                    'summary' => 'Payment overdue escalation & clinic feedback',
                ],
                [
                    'type' => 'Call',
                    'detail' => 'Discussed overdue Term 2 payment balance (₹237,500). Dr. Mehra stated CFO was traveling and promised release by Tuesday.',
                    'happened_on' => now()->subDays(2)->toDateString(),
                    'next_date' => now()->addDays(2)->toDateString(),
                    'next_note' => 'Verify bank credit for remaining Term 2 balance.',
                    'created_by' => $sneha->id,
                ]
            );

            Followup::updateOrCreate(
                [
                    'customer_id' => $c2->id,
                    'project_id' => $p2?->id,
                    'summary' => 'HIPAA Security & compliance architecture review',
                ],
                [
                    'type' => 'Email',
                    'detail' => 'Sent end-to-end encryption specs and AWS KMS envelope encryption documentation for security team audit sign-off.',
                    'happened_on' => now()->subDays(12)->toDateString(),
                    'next_date' => null,
                    'next_note' => null,
                    'created_by' => $sneha->id,
                ]
            );
        }

        // Customer CUS-0003 (Bharat Retail)
        $c3 = Customer::where('customer_code', 'CUS-0003')->first();
        $p3 = Project::where('project_code', 'PJT-0003')->first();
        if ($c3) {
            Followup::updateOrCreate(
                [
                    'customer_id' => $c3->id,
                    'project_id' => $p3?->id,
                    'summary' => 'Critical delay mitigation & ERP sync blocker resolution',
                ],
                [
                    'type' => 'WhatsApp',
                    'detail' => 'Followed up on WhatsApp group regarding SAP staging sandbox IP whitelist. Client IT team promised unblock by 4 PM today.',
                    'happened_on' => now()->subDay()->toDateString(),
                    'next_date' => now()->toDateString(),
                    'next_note' => 'Verify SAP connection from staging node.',
                    'created_by' => $rohan->id,
                ]
            );

            Followup::updateOrCreate(
                [
                    'customer_id' => $c3->id,
                    'project_id' => $p3?->id,
                    'summary' => 'Firm payment reminder for Term 2 and Term 3 dues',
                ],
                [
                    'type' => 'Call',
                    'detail' => 'Called procurement head regarding ₹450,000 total overdue amount across milestone terms. Promised partial release upon staging verification.',
                    'happened_on' => now()->subDays(6)->toDateString(),
                    'next_date' => now()->addDays(3)->toDateString(),
                    'next_note' => 'Call accounting department for RTGS UTR number.',
                    'created_by' => $sameer->id,
                ]
            );
        }

        // Customer CUS-0004 (Zeta FinTech)
        $c4 = Customer::where('customer_code', 'CUS-0004')->first();
        $p4 = Project::where('project_code', 'PJT-0004')->first();
        if ($c4) {
            Followup::updateOrCreate(
                [
                    'customer_id' => $c4->id,
                    'project_id' => $p4?->id,
                    'summary' => 'Monthly engineering retainer governance sync',
                ],
                [
                    'type' => 'Meeting',
                    'detail' => 'Monthly steering committee call. Reviewed burndown chart, velocity, and upcoming month roadmap with product VP Priya Iyer.',
                    'happened_on' => now()->subDays(5)->toDateString(),
                    'next_date' => now()->addDays(20)->toDateString(),
                    'next_note' => 'Prepare Sprint Retainer invoice for next month.',
                    'created_by' => $ananya->id,
                ]
            );
        }

        // Customer CUS-0005 (Nimbus Cloud)
        $c5 = Customer::where('customer_code', 'CUS-0005')->first();
        $p5 = Project::where('project_code', 'PJT-0005')->first();
        if ($c5) {
            Followup::updateOrCreate(
                [
                    'customer_id' => $c5->id,
                    'project_id' => $p5?->id,
                    'summary' => 'AMC Renewal Proposal Discussion',
                ],
                [
                    'type' => 'Meeting',
                    'detail' => 'Met with operations VP at Bengaluru facility. AMC is expiring in 25 days. Handed over annual maintenance package proposal for ₹90,000/yr.',
                    'happened_on' => now()->subDays(4)->toDateString(),
                    'next_date' => now()->addDays(7)->toDateString(),
                    'next_note' => 'Follow up on AMC purchase order sign-off before contract lapses.',
                    'created_by' => $kavita->id,
                ]
            );

            Followup::updateOrCreate(
                [
                    'customer_id' => $c5->id,
                    'project_id' => $p5?->id,
                    'summary' => 'Post-deployment stabilization review note',
                ],
                [
                    'type' => 'Note',
                    'detail' => 'Internal note: Kiosk uptime has reached 99.98% over past 30 days. No critical incidents reported in triage ticketing.',
                    'happened_on' => now()->subDays(14)->toDateString(),
                    'next_date' => null,
                    'next_note' => null,
                    'created_by' => $aakash->id,
                ]
            );
        }

        // Customer CUS-0007 (BioPure Solutions)
        $c7 = Customer::where('customer_code', 'CUS-0007')->first();
        $p7 = Project::where('project_code', 'PJT-0007')->first();
        if ($c7) {
            Followup::updateOrCreate(
                [
                    'customer_id' => $c7->id,
                    'project_id' => $p7?->id,
                    'summary' => 'Project onboarding and MQTT sensor gateway telemetry test',
                ],
                [
                    'type' => 'Call',
                    'detail' => 'Spoke with Chief Agronomist regarding firmware payload schema. Sensor packets validated on test staging listener.',
                    'happened_on' => now()->subDays(1)->toDateString(),
                    'next_date' => now()->addDays(3)->toDateString(),
                    'next_note' => 'Deliver signed onboarding handover checklist.',
                    'created_by' => $rohan->id,
                ]
            );
        }
    }
}
