<?php

namespace Tests\Feature;

use App\Livewire\Settings\SettingIndex;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $coordinator;

    protected User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'name' => 'Admin User',
            'email' => 'admin@test.com',
        ]);

        $this->manager = User::factory()->create([
            'role' => 'Manager',
            'status' => 'Active',
            'name' => 'Manager User',
            'email' => 'manager@test.com',
        ]);

        $this->coordinator = User::factory()->create([
            'role' => 'Coordinator',
            'status' => 'Active',
            'name' => 'Coordinator User',
            'email' => 'coordinator@test.com',
        ]);

        $this->sales = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Active',
            'name' => 'Sales User',
            'email' => 'sales@test.com',
        ]);
    }

    public function test_only_admin_can_access_settings(): void
    {
        $this->actingAs($this->admin)->get('/settings')->assertStatus(200);
        $this->actingAs($this->manager)->get('/settings')->assertStatus(403);
        $this->actingAs($this->coordinator)->get('/settings')->assertStatus(403);
        $this->actingAs($this->sales)->get('/settings')->assertStatus(403);
    }

    public function test_admin_can_add_dynamic_reminder_thresholds_including_day_zero(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SettingIndex::class)
            ->set('newReminderDay', 14)
            ->call('addReminderDay')
            ->assertHasNoErrors()
            ->assertSet('reminderDays', [14, 7, 3, 1])
            // Add Day 0 (on due date)
            ->set('newReminderDay', 0)
            ->call('addReminderDay')
            ->assertHasNoErrors()
            ->assertSet('reminderDays', [14, 7, 3, 1, 0])
            ->assertSee('Due Day')
            ->assertDontSee('Day 0');
    }

    public function test_cannot_add_duplicate_or_out_of_range_threshold(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SettingIndex::class)
            // Existing value 7
            ->set('newReminderDay', 7)
            ->call('addReminderDay')
            ->assertHasErrors(['newReminderDay'])
            // Out of range value > 180
            ->set('newReminderDay', 200)
            ->call('addReminderDay')
            ->assertHasErrors(['newReminderDay']);
    }

    public function test_admin_can_remove_threshold_and_apply_presets(): void
    {
        $comp = Livewire::actingAs($this->admin)
            ->test(SettingIndex::class)
            ->call('removeReminderDay', 3)
            ->assertSet('reminderDays', [7, 1]);

        // Apply intensive preset
        $comp->call('applyPreset', 'intensive')
            ->assertSet('reminderDays', [14, 7, 3, 1, 0]);

        // Apply minimal preset
        $comp->call('applyPreset', 'minimal')
            ->assertSet('reminderDays', [3, 0]);
    }

    public function test_admin_can_save_settings_and_persists_in_database(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SettingIndex::class)
            ->call('applyPreset', 'intensive')
            ->set('companyName', 'Apex Payment Systems')
            ->set('displayCurrency', 'INR')
            ->call('saveSettings')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertEquals('[14,7,3,1,0]', Setting::getValue('payment_alert_days'));
        $this->assertEquals('Apex Payment Systems', Setting::getValue('company_name'));
        $this->assertEquals('INR', Setting::getValue('display_currency'));

        $this->assertEquals([14, 7, 3, 1, 0], Setting::getPaymentReminderDays());
    }

    public function test_setting_renders_user_avatar_image(): void
    {
        $this->admin->update(['avatar' => 'avatars/test-avatar_12345.png']);

        Livewire::actingAs($this->admin)
            ->test(SettingIndex::class)
            ->assertSee($this->admin->avatar_url);
    }

    public function test_setting_helper_handles_backwards_compatibility(): void
    {
        // Legacy single integer in database
        Setting::query()->updateOrCreate(['key' => 'payment_alert_days'], ['value' => '10']);
        $this->assertEquals([10], Setting::getPaymentReminderDays());

        // Comma-separated string in database
        Setting::query()->updateOrCreate(['key' => 'payment_alert_days'], ['value' => '14, 7, 0']);
        $this->assertEquals([14, 7, 0], Setting::getPaymentReminderDays());

        // Clean JSON array in database
        Setting::query()->updateOrCreate(['key' => 'payment_alert_days'], ['value' => json_encode([21, 14, 7, 0])]);
        $this->assertEquals([21, 14, 7, 0], Setting::getPaymentReminderDays());
    }
}
