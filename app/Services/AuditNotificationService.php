<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Throwable;

class AuditNotificationService {
    public function log(string $who, string $action, string $entity, ?int $entityId = null, string $code = '', string $detail = ''): void {
        try {
            AuditLog::query()->create([
                'who' => mb_substr($who, 0, 80),
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

    public function notify(string $email, string $kind, string $title, string $body, string $linkType = '', ?int $linkId = null): void {
        if (empty($email)) {
            return;
        }

        try {
            Notification::query()->create([
                'user_email' => mb_substr($email, 0, 160),
                'kind' => mb_substr($kind, 0, 40),
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

    public function notifyCoordinators(string $kind, string $title, string $body, string $linkType = '', ?int $linkId = null): void {
        try {
            $emails = User::query()->where('status', 'Active')->pluck('email');
            foreach ($emails as $email) {
                $this->notify($email, $kind, $title, $body, $linkType, $linkId);
            }
        } catch (Throwable) {
            // Non-blocking
        }
    }
}
