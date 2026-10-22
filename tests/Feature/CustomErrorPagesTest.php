<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_sees_in_workspace_404_error_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Alice Coordinator',
            'role' => 'Coordinator',
            'password' => 'Password123!',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response = $this->get('/non-existent-route-for-testing-404');

        $response->assertStatus(404);
        $response->assertSee('404 · Page Not Found');
        $response->assertSee('Page Not Found');
        $response->assertSee('The page you requested could not be found');
        $response->assertSee('Return to Dashboard');
        $response->assertSee('Alice Coordinator');
        $response->assertSee('sidebar', false);
        $response->assertSee('sb-name', false);
    }

    public function test_dynamic_404_messages_for_missing_customers_and_projects(): void
    {
        $user = User::factory()->create([
            'role' => 'Admin',
        ]);

        // Customer missing 404
        $customerResponse = $this->actingAs($user)->get('/customer/99999');
        $customerResponse->assertStatus(404);
        $customerResponse->assertSee('Customer Not Found');
        $customerResponse->assertSee('The customer you requested does not exist');
        $customerResponse->assertSee('View All Customers');

        // Project missing 404
        $projectResponse = $this->actingAs($user)->get('/project/99999');
        $projectResponse->assertStatus(404);
        $projectResponse->assertSee('Project Not Found');
        $projectResponse->assertSee('The project you requested does not exist');
        $projectResponse->assertSee('View All Projects');
    }

    public function test_unauthenticated_guest_sees_branded_guest_404_error_page(): void
    {
        $response = $this->get('/non-existent-route-for-testing-404');

        $response->assertStatus(404);
        $response->assertSee('404 · Page Not Found');
        $response->assertSee('Return to Sign In');
        $response->assertDontSee('sb-name', false);
    }

    public function test_authenticated_user_forbidden_access_renders_in_workspace_403_page(): void
    {
        $salesUser = User::factory()->create([
            'role' => 'Sales',
        ]);

        // Attempting to access Reports index which is restricted to Admin/Manager
        $response = $this->actingAs($salesUser)->get(route('reports.index'));

        $response->assertStatus(403);
        $response->assertSee('403 · Access Restricted');
        $response->assertSee('Access Restricted');
        $response->assertSee('Return to My Workspace');
    }

    public function test_error_views_can_render_500_and_419_pages(): void
    {
        $user = User::factory()->create();

        $view500 = $this->actingAs($user)->view('errors.500');
        $view500->assertSee('500 · Server Error');
        $view500->assertSee('Unexpected Server Error');
        $view500->assertSee('Reload Page');

        $view419 = $this->actingAs($user)->view('errors.419');
        $view419->assertSee('419 · Session Expired');
        $view419->assertSee('Page Session Expired');
    }
}
