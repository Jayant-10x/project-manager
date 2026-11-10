<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicPageTitleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_routes_render_dynamic_titles(): void
    {
        $loginRes = $this->get('/login');
        $loginRes->assertOk();
        $this->assertStringContainsString(
            '<title>Sign In | Project Manager 10X Agency</title>',
            $loginRes->getContent()
        );

        $forgotRes = $this->get('/forgot-password');
        $forgotRes->assertOk();
        $this->assertStringContainsString(
            '<title>Forgot Password | Project Manager 10X Agency</title>',
            $forgotRes->getContent()
        );
    }

    public function test_authenticated_index_pages_render_dynamic_titles(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        $routes = [
            '/projects' => 'Projects | Project Manager 10X Agency',
            '/dashboard' => 'Dashboard | Project Manager 10X Agency',
            '/customers' => 'Customers | Project Manager 10X Agency',
            '/payments' => 'Payments | Project Manager 10X Agency',
            '/schedules' => 'Payment Schedule | Project Manager 10X Agency',
            '/team' => 'Team Members | Project Manager 10X Agency',
            '/users' => 'Users &amp; Access | Project Manager 10X Agency',
            '/settings' => 'Settings | Project Manager 10X Agency',
            '/activity-log' => 'Activity Log | Project Manager 10X Agency',
            '/today-work' => 'Today&#039;s Work | Project Manager 10X Agency',
            '/reports' => 'Reports &amp; Analytics | Project Manager 10X Agency',
            '/notifications' => 'Notifications | Project Manager 10X Agency',
        ];

        foreach ($routes as $url => $expectedTitle) {
            $response = $this->actingAs($admin)->get($url);
            $response->assertOk();
            $this->assertStringContainsString(
                "<title>{$expectedTitle}</title>",
                $response->getContent(),
                "Route {$url} did not render expected title: {$expectedTitle}"
            );
        }
    }

    public function test_detail_views_render_entity_names_in_title(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        $customer = Customer::create([
            'customer_code' => 'CUS-001',
            'company' => 'Acme Corporation',
            'contact_name' => 'John Doe',
            'phone' => '9876543210',
            'status' => 'Active',
        ]);

        $project = Project::create([
            'project_code' => 'PRJ-001',
            'name' => 'Enterprise CRM Portal',
            'customer_id' => $customer->id,
            'status' => 'In Progress',
            'total_amount' => 50000,
        ]);

        // Customer detail
        $custRes = $this->actingAs($admin)->get("/customer/{$customer->id}");
        $custRes->assertOk();
        $this->assertStringContainsString(
            "<title>{$customer->display_name} | Project Manager 10X Agency</title>",
            $custRes->getContent()
        );

        // Project detail
        $projRes = $this->actingAs($admin)->get("/project/{$project->id}");
        $projRes->assertOk();
        $this->assertStringContainsString(
            "<title>{$project->name} | Project Manager 10X Agency</title>",
            $projRes->getContent()
        );
    }

    public function test_error_pages_render_dynamic_titles(): void
    {
        $res = $this->get('/non-existent-page-url');
        $res->assertStatus(404);
        $this->assertStringContainsString(
            '<title>Page Not Found | Project Manager 10X Agency</title>',
            $res->getContent()
        );
    }
}
