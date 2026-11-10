<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPollController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $afterId = (int) $request->query('after_id', 0);
        $maxId = (int) (Notification::where('user_id', $userId)->max('id') ?? 0);
        $unreadCount = Notification::where('user_id', $userId)->where('is_read', false)->count();

        // 1. Initial client synchronization (prevent historical alert flood on very first device session)
        if ($request->boolean('init')) {
            return response()->json([
                'unread_count' => $unreadCount,
                'latest_id' => $maxId,
                'reset_cursor' => true,
                'items' => [],
            ]);
        }

        // 2. If database was cleared/truncated or client cursor is ahead of database reality,
        // reset client cursor to current max ID without triggering alerts.
        if ($afterId > $maxId) {
            return response()->json([
                'unread_count' => $unreadCount,
                'latest_id' => $maxId,
                'reset_cursor' => true,
                'items' => [],
            ]);
        }

        // 3. Query ONLY genuinely new notifications created after the client's last seen cursor
        $newNotifications = Notification::query()
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->where('id', '>', $afterId)
            ->orderBy('id', 'asc')
            ->limit(5)
            ->get(['id', 'title', 'body', 'type', 'link_type', 'link_id']);

        return response()->json([
            'unread_count' => $unreadCount,
            'latest_id' => $maxId,
            'reset_cursor' => false,
            'items' => $newNotifications,
        ]);
    }
}
