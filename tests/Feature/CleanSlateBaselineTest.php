<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanSlateBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_clean_slate_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'Admin',
            'status' => 'Active',
            'must_change' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        // Assert that enabled module routes are present
        $response->assertSee('/customers');
        $response->assertSee('/projects');
        $response->assertSee('/schedules');
        $response->assertSee('/today-work');
        // Assert that un-activated feature routes like my-tasks remain inactive in sidebar
        $response->assertDontSee('/my-tasks');
    }
}
