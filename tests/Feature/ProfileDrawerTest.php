<?php

namespace Tests\Feature;

use App\Livewire\Profile\ProfileDrawer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileDrawerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'John Coordinator',
            'email' => 'coordinator@test.com',
            'role' => 'Coordinator',
            'phone' => '+91 9876543210',
            'city' => 'Mumbai',
            'password' => 'Password123!',
            'status' => 'Active',
        ]);
    }

    public function test_user_can_open_drawer_and_loads_their_profile_data(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProfileDrawer::class)
            ->dispatch('open-profile-drawer')
            ->assertSet('isOpen', true)
            ->assertSet('name', 'John Coordinator')
            ->assertSet('email', 'coordinator@test.com')
            ->assertSet('phone', '+91 9876543210')
            ->assertSet('city', 'Mumbai')
            ->assertSet('role', 'Coordinator');
    }

    public function test_user_can_update_profile_details(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProfileDrawer::class)
            ->dispatch('open-profile-drawer')
            ->set('name', 'John Updated')
            ->set('phone', '+91 9123456789')
            ->set('city', 'Pune')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('isOpen', false)
            ->assertDispatched('toast')
            ->assertDispatched('profile-updated');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'John Updated',
            'phone' => '+91 9123456789',
            'city' => 'Pune',
        ]);
    }

    public function test_user_can_upload_and_remove_avatar(): void
    {
        Storage::fake('public');

        $fakeAvatar = UploadedFile::fake()->image('my-avatar.png', 120, 120);

        Livewire::actingAs($this->user)
            ->test(ProfileDrawer::class)
            ->dispatch('open-profile-drawer')
            ->set('avatarFile', $fakeAvatar)
            ->call('save')
            ->assertHasNoErrors();

        $updatedUser = $this->user->fresh();
        $this->assertNotNull($updatedUser->avatar);
        $this->assertStringStartsWith('avatars/my-avatar_', $updatedUser->avatar);
        $this->assertStringEndsWith('.png', $updatedUser->avatar);
        Storage::disk('public')->assertExists($updatedUser->avatar);

        // Now test removing the avatar
        Livewire::actingAs($updatedUser)
            ->test(ProfileDrawer::class)
            ->dispatch('open-profile-drawer')
            ->call('markRemoveAvatar')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($this->user->fresh()->avatar);
    }

    public function test_user_can_change_password_with_valid_current_password(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProfileDrawer::class)
            ->dispatch('open-profile-drawer')
            ->set('showPasswordSection', true)
            ->set('current_password', 'Password123!')
            ->set('new_password', 'NewSecurePass@2026')
            ->set('new_password_confirmation', 'NewSecurePass@2026')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertTrue(Hash::check('NewSecurePass@2026', $this->user->fresh()->getAuthPassword()));
    }

    public function test_user_cannot_change_password_with_incorrect_current_password(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProfileDrawer::class)
            ->dispatch('open-profile-drawer')
            ->set('showPasswordSection', true)
            ->set('current_password', 'WrongPassword!')
            ->set('new_password', 'NewSecurePass@2026')
            ->set('new_password_confirmation', 'NewSecurePass@2026')
            ->call('save')
            ->assertHasErrors(['current_password']);

        $this->assertFalse(Hash::check('NewSecurePass@2026', $this->user->fresh()->getAuthPassword()));
    }

    public function test_user_can_generate_random_password_in_drawer(): void
    {
        $comp = Livewire::actingAs($this->user)
            ->test(ProfileDrawer::class)
            ->dispatch('open-profile-drawer')
            ->call('generateRandomPassword');

        $newPass = $comp->get('new_password');
        $confPass = $comp->get('new_password_confirmation');

        $this->assertNotEmpty($newPass);
        $this->assertEquals($newPass, $confPass);
        $this->assertGreaterThanOrEqual(8, strlen($newPass));
    }
}
