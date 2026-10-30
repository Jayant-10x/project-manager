<?php

namespace Tests\Feature;

use App\Livewire\Auth\ForcePasswordChange;
use App\Livewire\Profile\ProfileDrawer;
use App\Livewire\Projects\ProjectRecurringModal;
use App\Livewire\Sales\SalesIndex;
use App\Livewire\Settings\SettingIndex;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLoggingCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'permissions' => ['all' => ['view', 'add', 'edit', 'delete']],
        ]);

        $this->manager = User::factory()->create([
            'role' => 'Manager',
            'status' => 'Active',
            'reporting_to' => $this->admin->id,
            'permissions' => ['all' => ['view', 'add', 'edit', 'delete']],
        ]);

        $this->sales = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Active',
            'reporting_to' => $this->manager->id,
            'permissions' => ['customers' => ['view', 'add', 'edit'], 'projects' => ['view', 'add', 'edit']],
        ]);
    }

    public function test_recurring_modal_generation_logs_activity_and_notifies_stakeholders(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::create([
            'company' => 'Acme Corp',
            'contact_name' => 'Alice',
            'customer_code' => 'CUST-REC-01',
            'status' => 'Active',
            'created_by' => $this->sales->id,
            'sales_person_id' => $this->sales->id,
        ]);

        $project = Project::create([
            'name' => 'Retainer Portal',
            'code' => 'PJT-REC-01',
            'project_code' => 'PJT-REC-01',
            'customer_id' => $customer->id,
            'sales_person_id' => $this->sales->id,
            'coordinator_id' => $this->manager->id,
            'created_by' => $this->admin->id,
            'value' => 60000.00,
            'status' => 'Onboarding',
            'health' => 'On Track',
            'start_date' => now()->toDateString(),
            'delivery_date' => now()->addMonths(6)->toDateString(),
        ]);

        Livewire::test(ProjectRecurringModal::class)
            ->call('load', $project->id)
            ->set('amount', 10000.00)
            ->set('months', 6)
            ->set('label', 'Monthly Retainer')
            ->call('generate')
            ->assertDispatched('project-saved')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Generated Recurring Schedule',
            'entity' => 'PaymentTerm',
            'entity_id' => $project->id,
            'entity_code' => 'PJT-REC-01',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->sales->id,
            'type' => 'Recurring Schedule Generated',
            'link_id' => $project->id,
        ]);
    }

    public function test_sales_index_deactivate_and_delete_logs_activity_and_notifies(): void
    {
        $this->actingAs($this->admin);

        // Salesperson with customer -> should be marked Inactive
        $customer = Customer::create([
            'company' => 'Attributed Client',
            'contact_name' => 'Bob',
            'customer_code' => 'CUST-SLS-01',
            'status' => 'Active',
            'sales_person_id' => $this->sales->id,
            'created_by' => $this->admin->id,
        ]);

        Livewire::test(SalesIndex::class)
            ->call('deleteSales', $this->sales->id);

        $this->sales->refresh();
        $this->assertEquals('Inactive', $this->sales->status);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Deactivated Salesperson',
            'entity' => 'User',
            'entity_id' => $this->sales->id,
        ]);

        // Salesperson without customers -> should be deleted
        $soloSales = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Active',
            'reporting_to' => $this->manager->id,
            'name' => 'Solo Rep',
            'email' => 'solo@example.com',
        ]);
        $soloId = $soloSales->id;

        Livewire::test(SalesIndex::class)
            ->call('deleteSales', $soloId);

        $this->assertDatabaseMissing('users', ['id' => $soloId]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Deleted Salesperson',
            'entity' => 'User',
            'entity_id' => $soloId,
        ]);
    }

    public function test_profile_drawer_save_logs_profile_update_and_password_change(): void
    {
        $this->actingAs($this->sales);

        Livewire::test(ProfileDrawer::class)
            ->call('openDrawer')
            ->set('name', 'Sales Person Updated')
            ->set('city', 'New City')
            ->set('showPasswordSection', true)
            ->set('current_password', 'password')
            ->set('new_password', 'NewSecret@123')
            ->set('new_password_confirmation', 'NewSecret@123')
            ->call('save')
            ->assertDispatched('profile-updated');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->sales->id,
            'action' => 'Updated Profile',
            'entity' => 'User',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->sales->id,
            'action' => 'Changed Password',
            'entity' => 'User',
        ]);
    }

    public function test_force_password_change_logs_activity(): void
    {
        $user = User::factory()->create([
            'must_change' => true,
            'password' => Hash::make('OldPassword1'),
        ]);

        $this->actingAs($user);

        Livewire::test(ForcePasswordChange::class)
            ->set('current_password', 'OldPassword1')
            ->set('new_password', 'NewSecurePass@123')
            ->set('new_password_confirmation', 'NewSecurePass@123')
            ->call('updatePassword')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'Force Changed Password',
            'entity' => 'User',
        ]);
    }

    public function test_setting_index_logout_logs_activity(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(SettingIndex::class)
            ->call('logout')
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'User Logout',
            'entity' => 'Auth',
        ]);
    }

    public function test_document_download_logs_activity(): void
    {
        Storage::fake('uploads');
        Storage::fake('public');

        $this->actingAs($this->admin);

        Storage::disk('uploads')->put('test-contracts/doc.pdf', 'dummy content');

        $doc = Document::create([
            'title' => 'Client Agreement',
            'file_name' => 'client_agreement.pdf',
            'stored_as' => 'test-contracts/doc.pdf',
            'file_size' => 1234,
            'file_type' => 'application/pdf',
            'category' => 'Contract',
            'uploaded_by' => $this->admin->id,
        ]);

        $response = $this->get(route('documents.download', $doc->id));
        $response->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'Downloaded Document',
            'entity' => 'Document',
            'entity_id' => $doc->id,
        ]);
    }

    public function test_failed_login_and_account_lockout_logs_activity(): void
    {
        $user = User::factory()->create([
            'email' => 'victim@example.com',
            'password' => Hash::make('CorrectPassword123'),
            'fail_count' => 4,
            'reporting_to' => $this->manager->id,
        ]);

        $response = $this->post('/login', [
            'email' => 'victim@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'Account Locked',
            'entity' => 'Auth',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->manager->id,
            'type' => 'Account Locked Alert',
        ]);
    }

    public function test_force_password_change_logout_logs_activity(): void
    {
        $user = User::factory()->create([
            'must_change' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(ForcePasswordChange::class)
            ->call('logout')
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'User Logout',
            'entity' => 'Auth',
        ]);
    }

    public function test_registered_user_controller_logs_activity(): void
    {
        $response = $this->post('/register', [
            'name' => 'John Doe New',
            'email' => 'johndoe_new@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $newUser = User::where('email', 'johndoe_new@example.com')->first();
        $this->assertNotNull($newUser);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $newUser->id,
            'action' => 'User Registered',
            'entity' => 'User',
        ]);
    }

    public function test_password_controller_update_logs_activity(): void
    {
        $this->actingAs($this->sales);

        $response = $this->put('/password', [
            'current_password' => 'password',
            'password' => 'NewSecPassword@123',
            'password_confirmation' => 'NewSecPassword@123',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->sales->id,
            'action' => 'Updated Password',
            'entity' => 'User',
        ]);
    }

    public function test_profile_controller_update_logs_activity(): void
    {
        $user = User::factory()->create([
            'name' => 'Profile User',
            'email' => 'profile_user@example.com',
        ]);

        $this->actingAs($user);

        $response = $this->patch('/profile', [
            'name' => 'Profile User Renamed',
            'email' => 'profile_user_new@example.com',
        ]);
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'Updated Profile',
            'entity' => 'User',
        ]);
    }

    public function test_profile_controller_destroy_logs_activity(): void
    {
        $user = User::factory()->create([
            'name' => 'Self Delete User',
            'email' => 'delete_me@example.com',
            'password' => Hash::make('MyPassword123'),
        ]);

        $this->actingAs($user);

        $deleteResponse = $this->delete('/profile', [
            'password' => 'MyPassword123',
        ]);
        $deleteResponse->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        // When user is deleted, foreign key constraint cascades user_id to NULL, but log entry persists
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Deleted Account',
            'entity' => 'User',
            'entity_id' => $user->id,
            'entity_code' => (string) $user->id,
        ]);
    }
}
