<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Throwable;

class AuditNotificationService
{
    public function log(string|int|null $who, string $action, string $entity, ?int $entityId = null, string $code = '', string $detail = '', ?array $changes = null): void
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
                'changes' => ! empty($changes) ? $changes : null,
                'at_time' => Carbon::now(),
            ]);
        } catch (Throwable) {
            // Audit failures must never interrupt primary execution flows
        }
    }

    /**
     * Compute field-level diffs between original and modified model attributes.
     */
    public function computeChanges($model, array $attributesToIgnore = ['updated_at', 'created_at', 'password', 'remember_token']): array
    {
        $changes = [];
        $dirty = $model->getDirty();
        foreach ($dirty as $key => $newVal) {
            if (in_array($key, $attributesToIgnore, true)) {
                continue;
            }
            $original = $model->getOriginal($key);
            if ($original != $newVal) {
                $changes[$key] = [
                    'old' => is_bool($original) ? ($original ? 'Yes' : 'No') : (string) ($original ?? '—'),
                    'new' => is_bool($newVal) ? ($newVal ? 'Yes' : 'No') : (string) ($newVal ?? '—'),
                ];
            }
        }

        return $changes;
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

    /**
     * Notify multiple users with automatic deduplication, self-exclusion, and custom exclusion list.
     *
     * @param  iterable<int|string>  $userIds
     * @param  array<int|string>  $excludeUserIds
     */
    public function notifyUsers(iterable $userIds, string $kind, string $title, string $body, string $linkType = '', ?int $linkId = null, array $excludeUserIds = []): void
    {
        $resolvedIds = [];
        $actorId = auth()->id();

        $excluded = array_map(fn ($id) => (int) $id, array_filter($excludeUserIds));
        if ($actorId) {
            $excluded[] = (int) $actorId;
        }

        foreach ($userIds as $userId) {
            if (empty($userId)) {
                continue;
            }
            $numericId = is_numeric($userId) ? (int) $userId : User::where('email', $userId)->value('id');
            if ($numericId && ! in_array($numericId, $excluded, true) && ! in_array($numericId, $resolvedIds, true)) {
                $recipientUser = User::find($numericId);
                if ($recipientUser && $recipientUser->isTechPerson()) {
                    // Suppress financial, customer, and collection notifications for Tech Person
                    $isUnrelated = in_array(strtolower($linkType), ['payment', 'payments', 'payment_term', 'schedule', 'customer', 'followup', 'followups', 'sales'], true)
                        || preg_match('/payment|installment|schedule|recurring|chase|customer|lead|invoice|tds|quota|sales|balance|due/i', $kind)
                        || preg_match('/payment|installment|schedule|recurring|chase|customer|lead|invoice|tds|quota|sales|balance|due/i', $title);

                    if ($isUnrelated) {
                        continue;
                    }
                }

                $resolvedIds[] = $numericId;
            }
        }

        foreach ($resolvedIds as $id) {
            $this->notify($id, $kind, $title, $body, $linkType, $linkId);
        }
    }

    /**
     * Notify upline managers and supervisors of a specific user or the authenticated user.
     *
     * @param  User|int|null  $user  Target user whose upline chain will be notified.
     * @param  bool  $includeAdmins  Whether to include active Admins as executive uplines.
     * @param  array<int>  $excludeUserIds  Additional user IDs to exclude from receiving notifications.
     */
    public function notifyUplines(User|int|null $user, string $kind, string $title, string $body, string $linkType = '', ?int $linkId = null, bool $includeAdmins = true, array $excludeUserIds = []): void
    {
        try {
            $userModel = $user instanceof User ? $user : ($user ? User::find($user) : auth()->user());
            if (! $userModel) {
                if ($includeAdmins) {
                    $adminIds = User::where('role', 'Admin')->where('status', 'Active')->pluck('id')->all();
                    $this->notifyUsers($adminIds, $kind, $title, $body, $linkType, $linkId, $excludeUserIds);
                }

                return;
            }

            $uplineIds = $userModel->getUplineUserIds($includeAdmins);
            $this->notifyUsers($uplineIds, $kind, $title, $body, $linkType, $linkId, $excludeUserIds);
        } catch (Throwable) {
            // Non-blocking
        }
    }

    /**
     * Notify all project stakeholders (coordinator, tech person, salesperson, creator, and their uplines).
     *
     * @param  Project  $project  The project model.
     * @param  array<int>  $excludeUserIds  Additional user IDs to exclude.
     */
    public function notifyProjectStakeholders(
        Project $project,
        string $kind,
        string $title,
        string $body,
        string $linkType = 'project',
        ?int $linkId = null,
        bool $includeUplines = true,
        bool $includeAdmins = true,
        array $excludeUserIds = []
    ): void {
        try {
            $targetLinkId = $linkId ?? $project->id;
            $recipientIds = [];

            $isFinancialOrCustomer = in_array(strtolower($linkType), ['payment', 'payments', 'payment_term', 'schedule', 'customer', 'followup', 'followups'], true)
                || preg_match('/payment|installment|schedule|recurring|chase|customer|invoice|tds/i', $kind)
                || preg_match('/payment|installment|schedule|recurring|chase|customer|invoice|tds/i', $title);

            if ($project->coordinator_id) {
                $recipientIds[] = (int) $project->coordinator_id;
            }
            $techPersonId = $project->tech_person_id;
            if ($techPersonId && ! $isFinancialOrCustomer) {
                $recipientIds[] = (int) $techPersonId;
            }
            if ($project->sales_person_id) {
                $recipientIds[] = (int) $project->sales_person_id;
            }
            if ($project->created_by) {
                $recipientIds[] = (int) $project->created_by;
            }

            if ($includeUplines) {
                $actorsToTrace = array_filter([
                    auth()->user(),
                    $project->coordinator_id ? User::find($project->coordinator_id) : null,
                    $project->sales_person_id ? User::find($project->sales_person_id) : null,
                    $techPersonId && ! $isFinancialOrCustomer ? User::find($techPersonId) : null,
                ]);

                foreach ($actorsToTrace as $actor) {
                    if ($actor instanceof User) {
                        $recipientIds = array_merge($recipientIds, $actor->getUplineUserIds(false));
                    }
                }
            }

            if ($includeAdmins) {
                $adminIds = User::where('role', 'Admin')->where('status', 'Active')->pluck('id')->all();
                $recipientIds = array_merge($recipientIds, $adminIds);
            }

            $this->notifyUsers($recipientIds, $kind, $title, $body, $linkType, $targetLinkId, $excludeUserIds);
        } catch (Throwable) {
            // Non-blocking
        }
    }

    /**
     * Notify all customer stakeholders (salesperson, creator, active project coordinators, and their uplines).
     *
     * @param  Customer  $customer  The customer model.
     * @param  array<int>  $excludeUserIds  Additional user IDs to exclude.
     */
    public function notifyCustomerStakeholders(
        Customer $customer,
        string $kind,
        string $title,
        string $body,
        string $linkType = 'customer',
        ?int $linkId = null,
        bool $includeUplines = true,
        bool $includeAdmins = true,
        array $excludeUserIds = []
    ): void {
        try {
            $targetLinkId = $linkId ?? $customer->id;
            $recipientIds = [];

            if ($customer->sales_person_id) {
                $recipientIds[] = (int) $customer->sales_person_id;
            }
            if ($customer->created_by) {
                $recipientIds[] = (int) $customer->created_by;
            }

            // Also check project coordinators associated with this customer
            $projectCoordinators = Project::where('customer_id', $customer->id)->whereNotNull('coordinator_id')->pluck('coordinator_id')->all();
            $recipientIds = array_merge($recipientIds, $projectCoordinators);

            if ($includeUplines) {
                $actorsToTrace = array_filter([
                    auth()->user(),
                    $customer->sales_person_id ? User::find($customer->sales_person_id) : null,
                ]);

                foreach ($actorsToTrace as $actor) {
                    if ($actor instanceof User) {
                        $recipientIds = array_merge($recipientIds, $actor->getUplineUserIds(false));
                    }
                }
            }

            if ($includeAdmins) {
                $adminIds = User::where('role', 'Admin')->where('status', 'Active')->pluck('id')->all();
                $recipientIds = array_merge($recipientIds, $adminIds);
            }

            $this->notifyUsers($recipientIds, $kind, $title, $body, $linkType, $targetLinkId, $excludeUserIds);
        } catch (Throwable) {
            // Non-blocking
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
