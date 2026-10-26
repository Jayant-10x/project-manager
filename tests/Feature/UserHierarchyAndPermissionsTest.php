<?php

namespace Tests\Feature;

use App\Constants\Permissions;
use App\Livewire\Settings\UserModal;
use App\Livewire\Team\TeamIndex;
use App\Livewire\Users\UserIndex;
use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use App\Services\PasswordGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UserHierarchyAndPermissionsTest extends TestCase
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
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'role' => 'Admin',
            'status' => 'Active',
            'must_change' => false,
        ]);

        $this->manager = User::factory()->create([
            'name' => 'Operations Manager',
            'email' => 'manager@test.com',
            'role' => 'Manager',
            'status' => 'Active',
            'must_change' => false,
            'reporting_to' => $this->admin->id,
        ]);

        $this->coordinator = User::factory()->create([
            'name' => 'Project Coordinator',
            'email' => 'coordinator@test.com',
            'role' => 'Coordinator',
            'status' => 'Active',
            'must_change' => false,
            'reporting_to' => $this->manager->id,
        ]);

        $this->sales = User::factory()->create([
            'name' => 'Sales Person',
            'email' => 'sales@test.com',
            'role' => 'Sales',
            'status' => 'Active',
            'must_change' => false,
            'reporting_to' => $this->coordinator->id,
        ]);
    }

    public function test_multi_level_hierarchy_permissions_resolution(): void
    {
        // Admin has full CRUD on users and view on team
        $this->assertTrue($this->admin->hasPermission('users', 'view'));
        $this->assertTrue($this->admin->hasPermission('users', 'add'));
        $this->assertTrue($this->admin->hasPermission('users', 'edit'));
        $this->assertTrue($this->admin->hasPermission('users', 'delete'));
        $this->assertTrue($this->admin->hasPermission('team', 'view'));
        // Dot notation support
        $this->assertTrue($this->admin->hasPermission('users.view'));
        $this->assertTrue($this->admin->hasPermission('users.add'));

        // Manager has only team view
        $this->assertFalse($this->manager->hasPermission('users', 'view'));
        $this->assertFalse($this->manager->hasPermission('users', 'add'));
        $this->assertFalse($this->manager->hasPermission('users', 'edit'));
        $this->assertFalse($this->manager->hasPermission('users', 'delete'));
        $this->assertTrue($this->manager->hasPermission('team', 'view'));

        // Coordinator has only team view
        $this->assertFalse($this->coordinator->hasPermission('users', 'view'));
        $this->assertFalse($this->coordinator->hasPermission('users', 'add'));
        $this->assertTrue($this->coordinator->hasPermission('team', 'view'));

        // Sales has no permissions on users or team
        $this->assertFalse($this->sales->hasPermission('users', 'view'));
        $this->assertFalse($this->sales->hasPermission('users', 'add'));
        $this->assertFalse($this->sales->hasPermission('team', 'view'));
    }

    public function test_admin_can_access_users_index_and_view_cards(): void
    {
        $response = $this->actingAs($this->admin)->get('/users');

        $response->assertStatus(200);
        $response->assertSee('Users & Access');
        $response->assertSee('Super Admin');
        $response->assertSee('Operations Manager');
        $response->assertSee('Project Coordinator');
        $response->assertSee('Sales Person');
        $response->assertSee('Add New User');

        // Sidebar shows Users
        $response->assertSee(route('users.index'));
        // Master layout includes the user modal
        $response->assertSee('data-modal="user-modal"', false);
    }

    public function test_admin_can_add_user_via_user_modal(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UserModal::class)
            ->dispatch('open-user-modal', ['id' => null, 'role' => 'Coordinator'])
            ->set('name', 'New Team Member')
            ->set('email', 'newmember@test.com')
            ->set('phone', '+91 9999988888')
            ->set('role', 'Coordinator')
            ->set('reporting_to', $this->manager->id)
            ->set('designation', 'Junior Coordinator')
            ->set('city', 'Mumbai')
            ->set('status', 'Active')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('user-saved')
            ->assertDispatched('close-modal');

        $this->assertDatabaseHas('users', [
            'email' => 'newmember@test.com',
            'name' => 'New Team Member',
            'role' => 'Coordinator',
            'reporting_to' => $this->manager->id,
        ]);
    }

    public function test_admin_can_edit_existing_user_via_user_modal(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UserModal::class)
            ->dispatch('open-user-modal', ['id' => $this->sales->id])
            ->set('name', 'Updated Sales Rep')
            ->set('designation', 'Senior Sales Rep')
            ->set('target', 850000)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('user-saved');

        $this->assertDatabaseHas('users', [
            'id' => $this->sales->id,
            'name' => 'Updated Sales Rep',
            'designation' => 'Senior Sales Rep',
            'target' => 850000,
        ]);
    }

    public function test_admin_can_upload_user_avatar_with_original_name_and_timestamp(): void
    {
        Storage::fake('public');
        $fakeAvatar = UploadedFile::fake()->image('staff_picture.jpg', 100, 100);

        Livewire::actingAs($this->admin)
            ->test(UserModal::class)
            ->dispatch('open-user-modal', ['id' => $this->sales->id])
            ->set('avatarFile', $fakeAvatar)
            ->call('save')
            ->assertHasNoErrors();

        $updated = $this->sales->fresh();
        $this->assertNotNull($updated->avatar);
        $this->assertStringStartsWith('avatars/staff-picture_', $updated->avatar);
        $this->assertStringEndsWith('.jpg', $updated->avatar);
        Storage::disk('public')->assertExists($updated->avatar);
    }

    public function test_non_admin_cannot_save_in_user_modal(): void
    {
        Livewire::actingAs($this->manager)
            ->test(UserModal::class)
            ->set('name', 'Hacker')
            ->set('email', 'hacker@test.com')
            ->set('role', 'Admin')
            ->set('status', 'Active')
            ->call('save')
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('users', ['email' => 'hacker@test.com']);
    }

    public function test_admin_can_view_single_user_details_in_modal(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UserIndex::class)
            ->call('viewUser', $this->sales->id)
            ->assertSet('viewUserId', $this->sales->id)
            ->assertSee('Sales Person')
            ->assertSee('sales@test.com');
    }

    public function test_admin_cannot_delete_self_or_last_active_admin(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UserIndex::class)
            ->call('deleteUser', $this->admin->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_non_admin_cannot_access_users_index(): void
    {
        $this->actingAs($this->manager)->get('/users')->assertStatus(403);
        $this->actingAs($this->coordinator)->get('/users')->assertStatus(403);
        $this->actingAs($this->sales)->get('/users')->assertStatus(403);
    }

    public function test_manager_can_access_team_index_and_sees_only_downline(): void
    {
        $response = $this->actingAs($this->manager)->get('/team');

        $response->assertStatus(200);
        $response->assertSee('Team Members');
        $response->assertSee('Project Coordinator');
        $response->assertSee('Sales Person');
        // Does not show Admin in downline
        $response->assertDontSee('Super Admin');
        // No Add User button on Team module
        $response->assertDontSee('Add New User');

        // Sidebar shows Team Members
        $response->assertSee(route('team.index'));
        $response->assertDontSee(route('users.index'));
    }

    public function test_coordinator_can_access_team_index_and_sees_reporting_members(): void
    {
        $response = $this->actingAs($this->coordinator)->get('/team');

        $response->assertStatus(200);
        $response->assertSee('Team Members');
        $response->assertSee('Sales Person');
        $response->assertDontSee('Operations Manager');
        $response->assertDontSee('Super Admin');
    }

    public function test_sales_person_cannot_access_team_or_users_and_sees_no_module_in_sidebar(): void
    {
        $this->actingAs($this->sales)->get('/team')->assertStatus(403);
        $this->actingAs($this->sales)->get('/users')->assertStatus(403);

        $response = $this->actingAs($this->sales)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertDontSee(route('users.index'));
        $response->assertDontSee(route('team.index'));
    }

    public function test_manager_can_view_team_member_profile_read_only(): void
    {
        Livewire::actingAs($this->manager)
            ->test(TeamIndex::class)
            ->call('viewMember', $this->sales->id)
            ->assertSet('viewMemberId', $this->sales->id)
            ->assertSee('Sales Person')
            ->assertSee('sales@test.com')
            ->assertDontSee('Edit User');
    }

    public function test_supervisors_are_filtered_dynamically_based_on_role(): void
    {
        $comp = Livewire::actingAs($this->admin)->test(UserModal::class);

        // When role is Manager, supervisors are Admins
        $comp->set('role', 'Manager');
        $supervisorsForManager = $comp->get('supervisors');
        $this->assertTrue($supervisorsForManager->contains('id', $this->admin->id));
        $this->assertFalse($supervisorsForManager->contains('id', $this->coordinator->id));

        // When role is Coordinator, supervisors are Managers
        $comp->set('role', 'Coordinator');
        $supervisorsForCoordinator = $comp->get('supervisors');
        $this->assertTrue($supervisorsForCoordinator->contains('id', $this->manager->id));
        $this->assertFalse($supervisorsForCoordinator->contains('id', $this->sales->id));

        // When role is Sales, supervisors are ONLY Coordinators
        $comp->set('role', 'Sales');
        $supervisorsForSales = $comp->get('supervisors');
        $this->assertTrue($supervisorsForSales->contains('id', $this->coordinator->id));
        $this->assertFalse($supervisorsForSales->contains('id', $this->manager->id));
        $this->assertFalse($supervisorsForSales->contains('id', $this->admin->id));
    }

    public function test_generate_random_password_produces_valid_secure_password(): void
    {
        $comp = Livewire::actingAs($this->admin)
            ->test(UserModal::class)
            ->call('generateRandomPassword');

        $generatedPassword = $comp->get('password');
        $this->assertNotEmpty($generatedPassword);
        $this->assertGreaterThanOrEqual(8, strlen($generatedPassword));
        $this->assertMatchesRegularExpression('/[A-Z]/', $generatedPassword);
        $this->assertMatchesRegularExpression('/[a-z]/', $generatedPassword);
        $this->assertMatchesRegularExpression('/[0-9]/', $generatedPassword);
    }

    public function test_admin_can_toggle_user_status_with_safeguards(): void
    {
        // Toggle Sales from Active to Inactive
        Livewire::actingAs($this->admin)
            ->test(UserIndex::class)
            ->call('toggleUserStatus', $this->sales->id)
            ->assertDispatched('toast');

        $this->assertEquals('Inactive', $this->sales->fresh()->status);

        // Toggle back to Active
        Livewire::actingAs($this->admin)
            ->test(UserIndex::class)
            ->call('toggleUserStatus', $this->sales->id)
            ->assertDispatched('toast');

        $this->assertEquals('Active', $this->sales->fresh()->status);

        // Cannot toggle self
        Livewire::actingAs($this->admin)
            ->test(UserIndex::class)
            ->call('toggleUserStatus', $this->admin->id)
            ->assertDispatched('toast');

        $this->assertEquals('Active', $this->admin->fresh()->status);
    }

    public function test_reset_permissions_to_role_defaults_restores_clean_state(): void
    {
        $comp = Livewire::actingAs($this->admin)
            ->test(UserModal::class)
            ->call('openModal', $this->sales->id);

        $this->assertFalse($comp->get('customPermissions'));

        // Toggle a permission (e.g. allow payments view to sales)
        $comp->call('togglePermission', 'payments', 'view');
        $this->assertTrue($comp->get('customPermissions'));
        $perms = $comp->get('userPermissions');
        $this->assertTrue($perms['payments']['view']);

        // Now reset to role defaults
        $comp->call('resetPermissionsToRoleDefaults');
        $this->assertFalse($comp->get('customPermissions'));
        $resetPerms = $comp->get('userPermissions');
        $this->assertFalse($resetPerms['payments']['view']);

        // Save and verify null permissions persisted to DB (using role defaults)
        $comp->call('save');
        $this->assertNull($this->sales->fresh()->permissions);
    }

    public function test_sales_rep_target_progress_is_displayed_properly_in_users_module(): void
    {
        $this->sales->update([
            'target' => 100000.0,
        ]);

        $customer = Customer::create([
            'customer_code' => 'CUS-SLS-01',
            'company' => 'Target Test Corp',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
        ]);

        Project::create([
            'project_code' => 'PRJ-SLS-01',
            'name' => 'Target Project 1',
            'customer_id' => $customer->id,
            'sales_person_id' => $this->sales->id,
            'value' => 75000.0,
            'status' => 'In Progress',
        ]);

        // 1. In UserIndex card
        Livewire::actingAs($this->admin)
            ->test(UserIndex::class)
            ->assertSee('Target Progress')
            ->assertSee('75%')
            ->assertSee('On Track');

        // 2. In UserIndex viewUser modal
        Livewire::actingAs($this->admin)
            ->test(UserIndex::class)
            ->call('viewUser', $this->sales->id)
            ->assertSee('Target')
            ->assertSee('Performance')
            ->assertSee('75%')
            ->assertSee('Remaining to Goal:');

        // 3. In UserModal when editing sales rep
        Livewire::actingAs($this->admin)
            ->test(UserModal::class)
            ->dispatch('open-user-modal', ['id' => $this->sales->id])
            ->assertSee('Current Progress:')
            ->assertSee('75%')
            ->assertSee('1 deals');
    }

    public function test_password_generator_service_produces_wordless_random_passwords(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $password = PasswordGeneratorService::generate(16);
            $this->assertEquals(16, strlen($password));
            $this->assertStringNotContainsStringIgnoringCase('SecureTemp', $password);
            $this->assertStringNotContainsStringIgnoringCase('welcome', $password);
            $this->assertStringNotContainsStringIgnoringCase('password', $password);
            $this->assertMatchesRegularExpression('/[A-Z]/', $password);
            $this->assertMatchesRegularExpression('/[a-z]/', $password);
            $this->assertMatchesRegularExpression('/[0-9]/', $password);
            $this->assertMatchesRegularExpression('/[*#@!$%&?~+=^]/', $password);
        }
    }
}
