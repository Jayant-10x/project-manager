<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPollingTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_poll_notifications(): void
    {
        $response = $this->getJson(route('api.notifications.poll'));

        $response->assertUnauthorized();
    }

    public function test_initial_poll_synchronizes_cursor_without_historical_flood(): void
    {
        $user = User::factory()->create();

        // 3 pre-existing notifications
        for ($i = 1; $i <= 3; $i++) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'auth',
                'title' => "Historical Alert #{$i}",
                'body' => 'Past activity notice',
                'is_read' => false,
            ]);
        }

        $response = $this->actingAs($user)->getJson(route('api.notifications.poll', ['init' => 1]));

        $response->assertOk()
            ->assertJson([
                'unread_count' => 3,
                'reset_cursor' => true,
                'items' => [],
            ]);

        $this->assertEquals(3, $response->json('latest_id'));
    }

    public function test_subsequent_poll_returns_new_unread_notifications_for_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Historical notification
        $oldNotif = Notification::create([
            'user_id' => $user->id,
            'type' => 'project',
            'title' => 'Old Notification',
            'body' => 'Past notice',
            'is_read' => true,
        ]);

        $cursor = $oldNotif->id;

        // New notification for this user
        $newNotif = Notification::create([
            'user_id' => $user->id,
            'type' => 'project',
            'title' => 'Project Milestone Completed',
            'body' => 'Milestone was delivered by team.',
            'link_type' => 'project',
            'link_id' => 1,
            'is_read' => false,
        ]);

        // Notification for other user
        Notification::create([
            'user_id' => $otherUser->id,
            'type' => 'lead',
            'title' => 'Secret Lead Assigned',
            'body' => 'Confidential lead data',
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->getJson(route('api.notifications.poll', ['after_id' => $cursor]));

        $response->assertOk()
            ->assertJson([
                'unread_count' => 1,
                'latest_id' => $newNotif->id,
                'reset_cursor' => false,
            ]);

        $items = $response->json('items');
        $this->assertCount(1, $items);
        $this->assertSame($newNotif->id, $items[0]['id']);
        $this->assertSame('Project Milestone Completed', $items[0]['title']);
    }

    public function test_poll_resets_cursor_if_after_id_exceeds_database_max(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('api.notifications.poll', ['after_id' => 9999]));

        $response->assertOk()
            ->assertJson([
                'unread_count' => 0,
                'latest_id' => 0,
                'reset_cursor' => true,
                'items' => [],
            ]);
    }
}
