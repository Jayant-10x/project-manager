<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'company_name' => '10X Agency',
            'company_address' => '12/482 Civil Lines, VIP Road, Kanpur, UP 208001',
            'company_gst' => '09AAACH7409R1ZZ',
            'company_pan' => 'AAACH7409R',
            'payment_alert_days' => 7,
            'amc_alert_days' => 30,
            'followup_alert_days' => 2,
            'default_currency' => 'INR',
            'usd_inr_rate' => 83.50,
        ];

        foreach ($settings as $key => $val) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $val]
            );
        }
    }
}
