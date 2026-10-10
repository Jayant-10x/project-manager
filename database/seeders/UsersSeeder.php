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
                'pass_hash' => $passwordHash,
                'must_change' => false,
                'joined' => now()->toDateString(),
            ],
            [
                'name' => 'Lead Coordinator',
                'email' => 'coordinator@the10xagency.in',
                'phone' => '9876543211',
                'role' => 'Coordinator',
                'designation' => 'Project Coordinator',
                'city' => 'Kanpur',
                'status' => 'Active',
                'pass_hash' => $passwordHash,
                'must_change' => false,
                'joined' => now()->toDateString(),
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
