<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationsSeeder extends Seeder
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
        $p5 = Project::where('project_code', 'PJT-0005')->first();
        $c3 = Customer::where('customer_code', 'CUS-0003')->first();

        $notifications = [
            // Payment alert (Upcoming milestone)
            [
                'user_id' => $aakash->id,
                'type' => 'payment',
                'title' => 'Payment Due: Beta App & Dispatcher Release',
                'body' => 'Installment of ₹340,000 for TechCorp Logistics Portal is due in 5 days.',
                'link_type' => 'project',
                'link_id' => $p1?->id,
                'is_read' => false,
            ],
            // Overdue payment alert
            [
                'user_id' => $sneha->id,
                'type' => 'payment',
                'title' => 'Payment Overdue: Video Suite & Clinic Portal',
                'body' => 'Apex Healthcare Telemedicine has an overdue balance of ₹237,500 for over 18 days.',
                'link_type' => 'project',
                'link_id' => $p2?->id,
                'is_read' => false,
            ],
            // Milestone alert
            [
                'user_id' => $sneha->id,
                'type' => 'milestone',
                'title' => 'Milestone Passed: Patient EHR & Video Consultation Suite',
                'body' => 'Milestone marked completed. Triggered review workflow.',
                'link_type' => 'project',
                'link_id' => $p2?->id,
                'is_read' => true,
            ],
            // Project delay alert
            [
                'user_id' => $rohan->id,
                'type' => 'project',
                'title' => 'Project Health Delayed: Bharat Retail Storefront',
                'body' => 'Project health status marked as Delayed due to blocked ERP sync integration.',
                'link_type' => 'project',
                'link_id' => $p3?->id,
                'is_read' => false,
            ],
            // Follow-up reminder
            [
                'user_id' => $rohan->id,
                'type' => 'followup',
                'title' => 'Scheduled Follow-up Today: Bharat Retail',
                'body' => 'Callback scheduled with IT lead regarding SAP sandbox whitelist unblocking.',
                'link_type' => 'customer',
                'link_id' => $c3?->id,
                'is_read' => false,
            ],
            // AMC expiration alert
            [
                'user_id' => $aakash->id,
                'type' => 'amc',
                'title' => 'AMC Expiring in 25 Days: Nimbus Cloud Kiosk',
                'body' => 'Annual maintenance contract will expire next month. Ensure renewal agreement is signed.',
                'link_type' => 'project',
                'link_id' => $p5?->id,
                'is_read' => false,
            ],
            // Admin notification
            [
                'user_id' => $admin->id,
                'type' => 'notification',
                'title' => 'Monthly Revenue & Collections Summary',
                'body' => 'Collections this month reached ₹1,632,500 with ₹450,000 flagged in overdue status.',
                'link_type' => 'project',
                'link_id' => null,
                'is_read' => true,
            ],
        ];

        foreach ($notifications as $notifData) {
            Notification::updateOrCreate(
                [
                    'user_id' => $notifData['user_id'],
                    'title' => $notifData['title'],
                ],
                $notifData
            );
        }
    }
}
