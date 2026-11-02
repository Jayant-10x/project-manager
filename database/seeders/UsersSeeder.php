<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * Team Hierarchy Requirements:
     * 1. 1 Admin
     * 2. 2 Managers
     * 3. 3 Project Coordinators:
     *    - Coordinator 1 reports to Manager 1
     *    - Coordinator 2 & 3 report to Manager 2
     * 4. Multiple Sales Persons reporting dynamically to Coordinators
     */
    public function run(): void {
        $passwordHash = Hash::make('Sample@1123');

        // 1. One Admin (Top-level)
        $admin = User::updateOrCreate(
            ['email' => 'info@the10xagency.in'],
            [
                'name' => 'System Admin',
                'phone' => '9876543210',
                'role' => 'Admin',
                'designation' => 'Executive Director',
                'city' => 'Kanpur',
                'status' => 'Active',
                'password' => $passwordHash,
                'must_change' => false,
                'reporting_to' => null,
            ]
        );
        if (App::isLocal()) {
            // 2. Two Managers (Reporting to Admin)
            $manager1 = User::updateOrCreate(
                ['email' => 'manager1@the10xagency.in'],
                [
                    'name' => 'Vikram Malhotra',
                    'phone' => '9876543211',
                    'role' => 'Manager',
                    'designation' => 'Engineering Delivery Manager',
                    'city' => 'Mumbai',
                    'status' => 'Active',
                    'password' => $passwordHash,
                    'must_change' => false,
                    'reporting_to' => $admin->id,
                ]
            );

            $manager2 = User::updateOrCreate(
                ['email' => 'manager2@the10xagency.in'],
                [
                    'name' => 'Pooja Singhania',
                    'phone' => '9876543212',
                    'role' => 'Manager',
                    'designation' => 'Client Operations Manager',
                    'city' => 'Bengaluru',
                    'status' => 'Active',
                    'password' => $passwordHash,
                    'must_change' => false,
                    'reporting_to' => $admin->id,
                ]
            );

            // 3. Three Project Coordinators:
            // Coordinator 1 -> reports to Manager 1
            $coordinator1 = User::updateOrCreate(
                ['email' => 'coordinator1@the10xagency.in'],
                [
                    'name' => 'Aakash Verma',
                    'phone' => '9876543231',
                    'role' => 'Coordinator',
                    'designation' => 'Senior Project Coordinator',
                    'city' => 'Kanpur',
                    'status' => 'Active',
                    'password' => $passwordHash,
                    'must_change' => false,
                    'reporting_to' => $manager1->id,
                ]
            );

            // Coordinator 2 & 3 -> report to Manager 2
            $coordinator2 = User::updateOrCreate(
                ['email' => 'coordinator2@the10xagency.in'],
                [
                    'name' => 'Sneha Kulkarni',
                    'phone' => '9876543232',
                    'role' => 'Coordinator',
                    'designation' => 'Technical Project Coordinator',
                    'city' => 'Pune',
                    'status' => 'Active',
                    'password' => $passwordHash,
                    'must_change' => false,
                    'reporting_to' => $manager2->id,
                ]
            );

            $coordinator3 = User::updateOrCreate(
                ['email' => 'coordinator3@the10xagency.in'],
                [
                    'name' => 'Rohan Mehta',
                    'phone' => '9876543233',
                    'role' => 'Coordinator',
                    'designation' => 'Associate Project Coordinator',
                    'city' => 'Delhi',
                    'status' => 'Active',
                    'password' => $passwordHash,
                    'must_change' => false,
                    'reporting_to' => $manager2->id,
                ]
            );

            // 4. Multiple Sales Persons (Reporting dynamically across the 3 coordinators)
            $salesReps = [
                [
                    'name' => 'Arjun Nair',
                    'email' => 'sales1@the10xagency.in',
                    'phone' => '9876543221',
                    'designation' => 'Enterprise Account Executive',
                    'city' => 'Mumbai',
                    'target' => 5000000.00,
                    'coordinator' => $coordinator1,
                ],
                [
                    'name' => 'Kavita Rao',
                    'email' => 'sales2@the10xagency.in',
                    'phone' => '9876543222',
                    'designation' => 'Regional Sales Lead',
                    'city' => 'Bengaluru',
                    'target' => 4500000.00,
                    'coordinator' => $coordinator2,
                ],
                [
                    'name' => 'Sameer Khan',
                    'email' => 'sales3@the10xagency.in',
                    'phone' => '9876543223',
                    'designation' => 'Business Development Manager',
                    'city' => 'Delhi',
                    'target' => 3500000.00,
                    'coordinator' => $coordinator3,
                ],
                [
                    'name' => 'Ananya Joshi',
                    'email' => 'sales4@the10xagency.in',
                    'phone' => '9876543224',
                    'designation' => 'Inside Sales Specialist',
                    'city' => 'Hyderabad',
                    'target' => 3000000.00,
                    'coordinator' => $coordinator1,
                ],
                [
                    'name' => 'Yash Gupta',
                    'email' => 'sales5@the10xagency.in',
                    'phone' => '9876543245',
                    'designation' => 'Inside Sales Specialist',
                    'city' => 'Kanpur',
                    'target' => 3500000.00,
                    'coordinator' => $coordinator1,
                ],
            ];

            foreach ($salesReps as $rep) {
                User::updateOrCreate(
                    ['email' => $rep['email']],
                    [
                        'name' => $rep['name'],
                        'phone' => $rep['phone'],
                        'role' => 'Sales',
                        'designation' => $rep['designation'],
                        'city' => $rep['city'],
                        'target' => $rep['target'],
                        'status' => 'Active',
                        'password' => $passwordHash,
                        'must_change' => false,
                        'reporting_to' => $rep['coordinator']->id,
                    ]
                );
            }
            $techPersons = [
                [
                    'name' => 'Sahil Khan',
                    'email' => 'tech1@the10xagency.in',
                    'phone' => '123123123123',
                    'designation' => 'Wordpress Developer',
                    'city' => 'Noida',
                    'target' => 0.00,
                    'reporting_to' => $manager1,
                ],
                [
                    'name' => 'Abdurrahman Ansari',
                    'email' => 'tech2@the10xagency.in',
                    'phone' => '6655447788',
                    'designation' => 'Full Stack Developer',
                    'city' => 'Kerala',
                    'target' => 0.00,
                    'reporting_to' => $manager2,
                ],
                [
                    'name' => 'Utkarsh Shukla',
                    'email' => 'tech3@the10xagency.in',
                    'phone' => '6655112233',
                    'designation' => 'SEO Developer',
                    'city' => 'Ghaziabad',
                    'target' => 0.00,
                    'reporting_to' => $manager1,
                ],
            ];

            foreach ($techPersons as $techPerson) {
                $existing = User::where('email', $techPerson['email'])
                    ->orWhere('phone', $techPerson['phone'])
                    ->orWhere('email', str_replace('tech', 'take', $techPerson['email']))
                    ->first();

                if ($existing) {
                    $existing->update([
                        'email' => $techPerson['email'],
                        'name' => $techPerson['name'],
                        'phone' => $techPerson['phone'],
                        'role' => 'TechPerson',
                        'designation' => $techPerson['designation'],
                        'city' => $techPerson['city'],
                        'target' => $techPerson['target'],
                        'status' => 'Active',
                        'reporting_to' => $techPerson['reporting_to']->id,
                    ]);
                } else {
                    User::create([
                        'name' => $techPerson['name'],
                        'email' => $techPerson['email'],
                        'phone' => $techPerson['phone'],
                        'role' => 'TechPerson',
                        'designation' => $techPerson['designation'],
                        'city' => $techPerson['city'],
                        'target' => $techPerson['target'],
                        'status' => 'Active',
                        'password' => $passwordHash,
                        'must_change' => false,
                        'reporting_to' => $techPerson['reporting_to']->id,
                    ]);
                }
            }
        }
    }
}
