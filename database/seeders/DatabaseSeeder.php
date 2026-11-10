<?php

namespace Database\Seeders;

use App\Services\CodeGeneratorService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UsersSeeder::class,
            //            SettingsSeeder::class,
            //            CustomersSeeder::class,
            //            ProjectsSeeder::class,
            //            MilestonesSeeder::class,
            //            PaymentTermsSeeder::class,
            //            PaymentsSeeder::class,
            //            FollowupsSeeder::class,
            //            TasksSeeder::class,
            //            DocumentsSeeder::class,
            //            NotificationsSeeder::class,
            //            ActivityLogsSeeder::class,
        ]);

        app(CodeGeneratorService::class)->syncAll();
    }
}
