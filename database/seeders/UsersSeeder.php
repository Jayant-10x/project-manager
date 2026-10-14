<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $passwordHash = Hash::make('Sample@1123');

        $users = [
            [
                'name' => 'System Admin',
                'email' => 'admin@the10xagency.in',
                'phone' => '9876543210',
                'role' => 'Admin',
                'designation' => 'System Administrator',
                'city' => 'Kanpur',
                'status' => 'Active',
                'password' => $passwordHash,
                'must_change' => false,
                'created_at' => now()->toDateString(),
            ],
            [
                'name' => 'Lead Coordinator',
                'email' => 'coordinator@the10xagency.in',
                'phone' => '9876543211',
                'role' => 'Coordinator',
                'designation' => 'Project Coordinator',
                'city' => 'Kanpur',
                'status' => 'Active',
                'password' => $passwordHash,
                'must_change' => false,
                'created_at' => now()->toDateString(),
            ],
            [
                'name' => 'Project Manager',
                'email' => 'manager@the10xagency.in',
                'phone' => '9876543213',
                'role' => 'Manager',
                'designation' => 'Manager',
                'city' => 'Kanpur',
                'status' => 'Active',
                'password' => $passwordHash,
                'must_change' => false,
                'created_at' => now()->toDateString(),
            ],
            [
                'name' => 'Lead Coordinator',
                'email' => 'coordinator@the10xagency.in',
                'phone' => '9876543291',
                'role' => 'Coordinator',
                'designation' => 'Project Coordinator',
                'city' => 'Kanpur',
                'status' => 'Active',
                'password' => $passwordHash,
                'must_change' => false,
                'created_at' => now()->toDateString(),
            ],
        ];

        foreach ($users as $userData) {
            User::query()->updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
