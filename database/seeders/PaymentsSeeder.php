<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'Admin')->first() ?? User::first();
        $coordinators = User::where('role', 'Project Coordinator')->get()->keyBy('name');

        // PJT-0001: TechCorp Logistics Portal (Advance: received 229,500, TDS: 25,500)
        $p1 = Project::where('project_code', 'PJT-0001')->first();
        if ($p1) {
            $term1 = PaymentTerm::where('project_id', $p1->id)->where('seq', 1)->first();
            Payment::updateOrCreate(
                ['code' => 'RCP-0001'],
                [
                    'project_id' => $p1->id,
                    'customer_id' => $p1->customer_id,
                    'term_id' => $term1?->id,
                    'amount' => 229500.00,
                    'tds' => 25500.00,
                    'paid_on' => now()->subMonths(3)->toDateString(),
                    'mode' => 'RTGS',
                    'reference' => 'RTGS-HDFC-982341209',
                    'bank' => 'HDFC Bank',
                    'note' => '30% Advance payment received via RTGS. TDS deducted under 194J (10%).',
                    'created_by' => $p1->coordinator_id ?? $admin->id,
                ]
            );
        }

        // PJT-0002: Apex Healthcare Telemedicine (Advance: 450,000 + 50,000 TDS, Partial Term 2: 180,000 + 20,000 TDS)
        $p2 = Project::where('project_code', 'PJT-0002')->first();
        if ($p2) {
            $term1 = PaymentTerm::where('project_id', $p2->id)->where('seq', 1)->first();
            Payment::updateOrCreate(
                ['code' => 'RCP-0002'],
                [
                    'project_id' => $p2->id,
                    'customer_id' => $p2->customer_id,
                    'term_id' => $term1?->id,
                    'amount' => 450000.00,
                    'tds' => 50000.00,
                    'paid_on' => now()->subMonths(4)->toDateString(),
                    'mode' => 'NEFT',
                    'reference' => 'NEFT-ICIC-482019382',
                    'bank' => 'ICICI Bank',
                    'note' => 'Kickoff advance 40% cleared via corporate NEFT.',
                    'created_by' => $p2->coordinator_id ?? $admin->id,
                ]
            );

            $term2 = PaymentTerm::where('project_id', $p2->id)->where('seq', 2)->first();
            Payment::updateOrCreate(
                ['code' => 'RCP-0003'],
                [
                    'project_id' => $p2->id,
                    'customer_id' => $p2->customer_id,
                    'term_id' => $term2?->id,
                    'amount' => 180000.00,
                    'tds' => 20000.00,
                    'paid_on' => now()->subDays(15)->toDateString(),
                    'mode' => 'NEFT',
                    'reference' => 'NEFT-ICIC-592837102',
                    'bank' => 'ICICI Bank',
                    'note' => 'Part-payment towards Video Suite milestone. Balance ₹237,500 pending.',
                    'created_by' => $p2->coordinator_id ?? $admin->id,
                ]
            );
        }

        // PJT-0003: Bharat Retail E-Commerce (Advance: 180,000 + 20,000 TDS)
        $p3 = Project::where('project_code', 'PJT-0003')->first();
        if ($p3) {
            $term1 = PaymentTerm::where('project_id', $p3->id)->where('seq', 1)->first();
            Payment::updateOrCreate(
                ['code' => 'RCP-0004'],
                [
                    'project_id' => $p3->id,
                    'customer_id' => $p3->customer_id,
                    'term_id' => $term1?->id,
                    'amount' => 180000.00,
                    'tds' => 20000.00,
                    'paid_on' => now()->subMonths(5)->toDateString(),
                    'mode' => 'Cheque',
                    'reference' => 'CHQ-839201',
                    'bank' => 'State Bank of India',
                    'note' => 'Account payee cheque cleared on presentation.',
                    'created_by' => $p3->coordinator_id ?? $admin->id,
                ]
            );
        }

        // PJT-0004: Zeta FinTech App (Retainer Month 1: 213,750 + 23,750 TDS)
        $p4 = Project::where('project_code', 'PJT-0004')->first();
        if ($p4) {
            $term1 = PaymentTerm::where('project_id', $p4->id)->where('seq', 1)->first();
            Payment::updateOrCreate(
                ['code' => 'RCP-0005'],
                [
                    'project_id' => $p4->id,
                    'customer_id' => $p4->customer_id,
                    'term_id' => $term1?->id,
                    'amount' => 213750.00,
                    'tds' => 23750.00,
                    'paid_on' => now()->startOfMonth()->addDays(2)->toDateString(),
                    'mode' => 'UPI',
                    'reference' => 'UPI-AXIS-938201948@okaxis',
                    'bank' => 'Axis Bank',
                    'note' => 'Retainer Month 1 disbursed directly through corporate UPI handle.',
                    'created_by' => $p4->coordinator_id ?? $admin->id,
                ]
            );
        }

        // PJT-0005: Nimbus Cloud Kiosk (Term 1 & Term 2: Fully paid)
        $p5 = Project::where('project_code', 'PJT-0005')->first();
        if ($p5) {
            $term1 = PaymentTerm::where('project_id', $p5->id)->where('seq', 1)->first();
            Payment::updateOrCreate(
                ['code' => 'RCP-0006'],
                [
                    'project_id' => $p5->id,
                    'customer_id' => $p5->customer_id,
                    'term_id' => $term1?->id,
                    'amount' => 225000.00,
                    'tds' => 25000.00,
                    'paid_on' => now()->subMonths(6)->toDateString(),
                    'mode' => 'RTGS',
                    'reference' => 'RTGS-KOTAK-394820192',
                    'bank' => 'Kotak Mahindra Bank',
                    'note' => '50% upfront commitment payment.',
                    'created_by' => $p5->coordinator_id ?? $admin->id,
                ]
            );

            $term2 = PaymentTerm::where('project_id', $p5->id)->where('seq', 2)->first();
            Payment::updateOrCreate(
                ['code' => 'RCP-0007'],
                [
                    'project_id' => $p5->id,
                    'customer_id' => $p5->customer_id,
                    'term_id' => $term2?->id,
                    'amount' => 225000.00,
                    'tds' => 25000.00,
                    'paid_on' => now()->subDays(20)->toDateString(),
                    'mode' => 'NEFT',
                    'reference' => 'NEFT-KOTAK-928374102',
                    'bank' => 'Kotak Mahindra Bank',
                    'note' => 'Final acceptance sign-off payment received in full.',
                    'created_by' => $p5->coordinator_id ?? $admin->id,
                ]
            );
        }

        // PJT-0006: EduLearn LMS Platform (Term 1 & Term 2: Fully paid)
        $p6 = Project::where('project_code', 'PJT-0006')->first();
        if ($p6) {
            $term1 = PaymentTerm::where('project_id', $p6->id)->where('seq', 1)->first();
            Payment::updateOrCreate(
                ['code' => 'RCP-0008'],
                [
                    'project_id' => $p6->id,
                    'customer_id' => $p6->customer_id,
                    'term_id' => $term1?->id,
                    'amount' => 337500.00,
                    'tds' => 37500.00,
                    'paid_on' => now()->subMonths(7)->toDateString(),
                    'mode' => 'Card',
                    'reference' => 'CORP-CARD-AUTH-83921',
                    'bank' => 'Standard Chartered',
                    'note' => 'Processed via corporate corporate credit card portal.',
                    'created_by' => $p6->coordinator_id ?? $admin->id,
                ]
            );

            $term2 = PaymentTerm::where('project_id', $p6->id)->where('seq', 2)->first();
            Payment::updateOrCreate(
                ['code' => 'RCP-0009'],
                [
                    'project_id' => $p6->id,
                    'customer_id' => $p6->customer_id,
                    'term_id' => $term2?->id,
                    'amount' => 337500.00,
                    'tds' => 37500.00,
                    'paid_on' => now()->subMonths(2)->toDateString(),
                    'mode' => 'NEFT',
                    'reference' => 'NEFT-STAN-394820183',
                    'bank' => 'Standard Chartered',
                    'note' => 'Final deployment & handover clearance.',
                    'created_by' => $p6->coordinator_id ?? $admin->id,
                ]
            );
        }

        // PJT-0007: BioPure IoT Pipeline (Term 1: Partial payment received 135,000 + 15,000 TDS)
        $p7 = Project::where('project_code', 'PJT-0007')->first();
        if ($p7) {
            $term1 = PaymentTerm::where('project_id', $p7->id)->where('seq', 1)->first();
            Payment::updateOrCreate(
                ['code' => 'RCP-0010'],
                [
                    'project_id' => $p7->id,
                    'customer_id' => $p7->customer_id,
                    'term_id' => $term1?->id,
                    'amount' => 135000.00,
                    'tds' => 15000.00,
                    'paid_on' => now()->subMonths(3)->toDateString(),
                    'mode' => 'UPI',
                    'reference' => 'UPI-YESB-738291039@yesbank',
                    'bank' => 'Yes Bank',
                    'note' => 'Partial advance paid online. Balance due on completion of architecture spec.',
                    'created_by' => $p7->coordinator_id ?? $admin->id,
                ]
            );
        }

        // Also add a direct cash receipt for customer CUS-0001 (PJT-0001 documentation / audit fee)
        if ($p1) {
            Payment::updateOrCreate(
                ['code' => 'RCP-0011'],
                [
                    'project_id' => $p1->id,
                    'customer_id' => $p1->customer_id,
                    'term_id' => null,
                    'amount' => 15000.00,
                    'tds' => 0.00,
                    'paid_on' => now()->subDays(10)->toDateString(),
                    'mode' => 'Cash',
                    'reference' => 'CASH-REC-0014',
                    'bank' => null,
                    'note' => 'Ad-hoc on-site training fee paid in cash with receipt voucher.',
                    'created_by' => $admin->id,
                ]
            );
        }
    }
}
