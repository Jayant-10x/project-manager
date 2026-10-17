<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Throwable;

class AuditNotificationService
{
    public function log(string|int|null $who, string $action, string $entity, ?int $entityId = null, string $code = '', string $detail = ''): void
    {
        try {
            $userId = null;
            if (is_numeric($who)) {
                $userId = (int) $who;
            } elseif (! empty($who)) {
                $userId = User::where('email', $who)->orWhere('name', $who)->value('id') ?? auth()->id();
            } else {
                $userId = auth()->id();
            }

            ActivityLog::query()->create([
                'user_id' => $userId,
                'action' => mb_substr($action, 0, 60),
                'entity' => mb_substr($entity, 0, 40),
                'entity_id' => $entityId,
                'entity_code' => mb_substr($code, 0, 20),
                'detail' => mb_substr($detail, 0, 400),
                'at_time' => Carbon::now(),
            ]);
        } catch (Throwable) {
            // Audit failures must never interrupt primary execution flows
        }
    }

    public function notify(string|int|null $recipient, string $kind, string $title, string $body, string $linkType = '', ?int $linkId = null): void
    {
        if (empty($recipient)) {
            return;
        }

        try {
            $userId = is_numeric($recipient) ? (int) $recipient : User::where('email', $recipient)->value('id');
            if (! $userId) {
                return;
            }

            Notification::query()->create([
                'user_id' => $userId,
                'type' => mb_substr($kind, 0, 40),
                'title' => mb_substr($title, 0, 200),
                'body' => mb_substr($body, 0, 300),
                'link_type' => mb_substr($linkType, 0, 20),
                'link_id' => $linkId,
                'is_read' => false,
            ]);
        } catch (Throwable) {
            // Notification failures must not interrupt transaction
        }
    }

    public function notifyCoordinators(string $kind, string $title, string $body, string $linkType = '', ?int $linkId = null): void
    {
        try {
            $userIds = User::query()->where('status', 'Active')->pluck('id');
            foreach ($userIds as $userId) {
                $this->notify($userId, $kind, $title, $body, $linkType, $linkId);
            }
        } catch (Throwable) {
            // Non-blocking
        }
    }
}
