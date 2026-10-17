<?php

namespace Database\Seeders;

use App\Models\PaymentTerm;
use App\Models\Project;
use Illuminate\Database\Seeder;

class PaymentTermsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::with('milestones')->get();

        foreach ($projects as $project) {
            $milestones = $project->milestones;

            switch ($project->project_code) {
                case 'PJT-0001': // ₹850,000 value: Advance (Paid), Mid project (Pending), Delivery (Pending)
                    $m2 = $milestones->where('sort_order', 3)->first();
                    $m3 = $milestones->where('sort_order', 5)->first();

                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 1],
                        [
                            'label' => 'Advance Commitment (30%)',
                            'amount' => 255000.00,
                            'percent' => 30.00,
                            'trigger_type' => 'fixed',
                            'due_date' => now()->subMonths(3)->toDateString(),
                            'grace_days' => 0,
                            'status' => 'Paid',
                            'received' => 229500.00,
                            'tds' => 25500.00, // 10% TDS
                            'notes' => 'Received via RTGS.',
                        ]
                    );

                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 2],
                        [
                            'label' => 'Beta App & Dispatcher Release (40%)',
                            'amount' => 340000.00,
                            'percent' => 40.00,
                            'trigger_type' => 'milestone',
                            'milestone_id' => $m2?->id,
                            'due_date' => now()->addDays(5)->toDateString(), // Upcoming alert!
                            'grace_days' => 7,
                            'status' => 'Pending',
                            'received' => 0.00,
                            'tds' => 0.00,
                            'notes' => 'Linked to Dispatcher Real-time Dashboard sign-off.',
                        ]
                    );

                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 3],
                        [
                            'label' => 'Live Deployment & Handover (30%)',
                            'amount' => 255000.00,
                            'percent' => 30.00,
                            'trigger_type' => 'milestone',
                            'milestone_id' => $m3?->id,
                            'due_date' => now()->addMonths(2)->toDateString(),
                            'grace_days' => 7,
                            'status' => 'Pending',
                            'received' => 0.00,
                            'tds' => 0.00,
                            'notes' => 'Final commercial milestone.',
                        ]
                    );
                    break;

                case 'PJT-0002': // ₹1,250,000 value: Advance (Paid), Term 2 (Partial/Overdue), Term 3 (Pending)
                    $m3 = $milestones->where('sort_order', 3)->first();
                    $m5 = $milestones->where('sort_order', 5)->first();

                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 1],
                        [
                            'label' => 'Project Kickoff Advance (40%)',
                            'amount' => 500000.00,
                            'percent' => 40.00,
                            'trigger_type' => 'fixed',
                            'due_date' => now()->subMonths(4)->toDateString(),
                            'grace_days' => 0,
                            'status' => 'Paid',
                            'received' => 450000.00,
                            'tds' => 50000.00,
                            'notes' => 'Cleared via HDFC Bank NEFT.',
                        ]
                    );

                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 2],
                        [
                            'label' => 'Video Suite & Clinic Portal (35%)',
                            'amount' => 437500.00,
                            'percent' => 35.00,
                            'trigger_type' => 'milestone',
                            'milestone_id' => $m3?->id,
                            'due_date' => now()->subDays(18)->toDateString(), // Overdue!
                            'grace_days' => 5,
                            'status' => 'Partial',
                            'received' => 180000.00,
                            'tds' => 20000.00,
                            'notes' => 'Partial payment received. ₹237,500 balance remaining overdue.',
                        ]
                    );

                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 3],
                        [
                            'label' => 'Security Audit & Final Delivery (25%)',
                            'amount' => 312500.00,
                            'percent' => 25.00,
                            'trigger_type' => 'milestone',
                            'milestone_id' => $m5?->id,
                            'due_date' => now()->addDays(10)->toDateString(),
                            'grace_days' => 5,
                            'status' => 'Pending',
                            'received' => 0.00,
                            'tds' => 0.00,
                            'notes' => 'Subject to HIPAA security audit.',
                        ]
                    );
                    break;

                case 'PJT-0003': // ₹650,000 value: Advance (Paid), Term 2 (Overdue), Term 3 (Overdue)
                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 1],
                        [
                            'label' => 'Contract Execution Advance',
                            'amount' => 200000.00,
                            'percent' => 30.77,
                            'trigger_type' => 'fixed',
                            'due_date' => now()->subMonths(5)->toDateString(),
                            'status' => 'Paid',
                            'received' => 180000.00,
                            'tds' => 20000.00,
                            'notes' => 'Initial payment.',
                        ]
                    );

                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 2],
                        [
                            'label' => 'Store & Gateway Integration Phase',
                            'amount' => 250000.00,
                            'percent' => 38.46,
                            'trigger_type' => 'fixed',
                            'due_date' => now()->subDays(45)->toDateString(), // Overdue!
                            'status' => 'Overdue',
                            'received' => 0.00,
                            'tds' => 0.00,
                            'notes' => 'Followed up 3 times. Client accounting review pending.',
                        ]
                    );

                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 3],
                        [
                            'label' => 'Warehouse Sync & Final Deployment',
                            'amount' => 200000.00,
                            'percent' => 30.77,
                            'trigger_type' => 'fixed',
                            'due_date' => now()->subDays(12)->toDateString(), // Overdue!
                            'status' => 'Overdue',
                            'received' => 0.00,
                            'tds' => 0.00,
                            'notes' => 'Linked to delayed project delivery.',
                        ]
                    );
                    break;

                case 'PJT-0004': // ₹950,000 value: Retainer / Monthly Schedule
                    $baseDate = now()->startOfMonth();
                    for ($m = 1; $m <= 4; $m++) {
                        $dueDate = $baseDate->copy()->addMonths($m - 1)->addDays(4);
                        $isPast = $dueDate->toDateString() < now()->toDateString();

                        PaymentTerm::updateOrCreate(
                            ['project_id' => $project->id, 'seq' => $m],
                            [
                                'label' => 'Sprint Retainer — '.$dueDate->format('M Y'),
                                'amount' => 237500.00,
                                'percent' => 25.00,
                                'trigger_type' => 'recurring',
                                'due_date' => $dueDate->toDateString(),
                                'grace_days' => 3,
                                'status' => $m === 1 ? 'Paid' : 'Pending',
                                'received' => $m === 1 ? 213750.00 : 0.00,
                                'tds' => $m === 1 ? 23750.00 : 0.00,
                                'notes' => 'Monthly engineering retainer installment.',
                            ]
                        );
                    }
                    break;

                case 'PJT-0005': // ₹500,000 value: Fully Paid
                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 1],
                        [
                            'label' => 'Commencement Advance',
                            'amount' => 250000.00,
                            'percent' => 50.00,
                            'trigger_type' => 'fixed',
                            'due_date' => now()->subMonths(6)->toDateString(),
                            'status' => 'Paid',
                            'received' => 225000.00,
                            'tds' => 25000.00,
                            'notes' => 'Paid via UPI/Bank transfer.',
                        ]
                    );

                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 2],
                        [
                            'label' => 'Kiosk Delivery & Final Sign-off',
                            'amount' => 250000.00,
                            'percent' => 50.00,
                            'trigger_type' => 'fixed',
                            'due_date' => now()->subDays(20)->toDateString(),
                            'status' => 'Paid',
                            'received' => 225000.00,
                            'tds' => 25000.00,
                            'notes' => 'Final settlement received.',
                        ]
                    );
                    break;

                case 'PJT-0006': // ₹750,000 value: Fully Paid
                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 1],
                        [
                            'label' => 'LMS Development Milestone 1',
                            'amount' => 375000.00,
                            'percent' => 50.00,
                            'trigger_type' => 'fixed',
                            'due_date' => now()->subMonths(7)->toDateString(),
                            'status' => 'Paid',
                            'received' => 337500.00,
                            'tds' => 37500.00,
                        ]
                    );

                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 2],
                        [
                            'label' => 'LMS Final Acceptance',
                            'amount' => 375000.00,
                            'percent' => 50.00,
                            'trigger_type' => 'fixed',
                            'due_date' => now()->subMonths(2)->toDateString(),
                            'status' => 'Paid',
                            'received' => 337500.00,
                            'tds' => 37500.00,
                        ]
                    );
                    break;

                case 'PJT-0007': // ₹600,000 value: Advance Partial, Term 2 Pending
                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 1],
                        [
                            'label' => 'Phase 1 Scoping',
                            'amount' => 300000.00,
                            'percent' => 50.00,
                            'trigger_type' => 'fixed',
                            'due_date' => now()->subMonths(3)->toDateString(),
                            'status' => 'Partial',
                            'received' => 135000.00,
                            'tds' => 15000.00,
                            'notes' => 'Client made 50% partial advance payment.',
                        ]
                    );

                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 2],
                        [
                            'label' => 'Phase 2 Smart Contract Audit',
                            'amount' => 300000.00,
                            'percent' => 50.00,
                            'trigger_type' => 'fixed',
                            'due_date' => now()->addMonths(1)->toDateString(),
                            'status' => 'Pending',
                            'received' => 0.00,
                            'tds' => 0.00,
                        ]
                    );
                    break;

                case 'PJT-0008': // Cancelled project terms
                    PaymentTerm::updateOrCreate(
                        ['project_id' => $project->id, 'seq' => 1],
                        [
                            'label' => 'Cancelled Engagement Term',
                            'amount' => 180000.00,
                            'percent' => 100.00,
                            'trigger_type' => 'fixed',
                            'due_date' => now()->subMonths(4)->toDateString(),
                            'status' => 'Cancelled',
                            'received' => 0.00,
                            'tds' => 0.00,
                            'notes' => 'Contract officially terminated.',
                        ]
                    );
                    break;
            }
        }
    }
}
