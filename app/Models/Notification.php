<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope query to smart notification category or explicit type.
     */
    public function scopeOfType($query, ?string $type)
    {
        if (empty($type)) {
            return $query;
        }

        $type = strtolower(trim($type));

        return match ($type) {
            'password_reset', 'auth', 'security', 'password' => $query->where(function (Builder $subQuery) {
                $subQuery->where('type', 'like', '%password%')
                    ->orWhere('type', 'like', '%locked%')
                    ->orWhere('type', 'like', '%auth%')
                    ->orWhere('type', 'like', '%security%')
                    ->orWhere('title', 'like', '%password%')
                    ->orWhere('title', 'like', '%locked%');
            }),
            'payment', 'payments' => $query->where(function (Builder $subQuery) {
                $subQuery->where('type', 'like', '%payment%')
                    ->orWhere('type', 'like', '%schedule%')
                    ->orWhere('type', 'like', '%installment%')
                    ->orWhere('title', 'like', '%payment%')
                    ->orWhere('title', 'like', '%installment%')
                    ->orWhere('link_type', 'payment')
                    ->orWhere('link_type', 'payments');
            }),
            'overdue' => $query->where(function (Builder $subQuery) {
                $subQuery->where('type', 'like', '%overdue%')
                    ->orWhere('title', 'like', '%overdue%');
            }),
            'milestone', 'milestones' => $query->where(function (Builder $subQuery) {
                $subQuery->where('type', 'like', '%milestone%')
                    ->orWhere('title', 'like', '%milestone%');
            }),
            'customer', 'customers' => $query->where(function (Builder $subQuery) {
                $subQuery->where('type', 'like', '%customer%')
                    ->orWhere('title', 'like', '%customer%')
                    ->orWhere('link_type', 'customer');
            }),
            'project', 'projects' => $query->where(function (Builder $subQuery) {
                $subQuery->where(function (Builder $projectSubQuery) {
                    $projectSubQuery->where('type', 'like', '%project%')
                        ->orWhere('type', 'handover')
                        ->orWhere('title', 'like', '%project%')
                        ->orWhere('link_type', 'project');
                })
                    ->where('type', 'not like', '%milestone%')
                    ->where('type', 'not like', '%payment%');
            }),
            'task', 'tasks' => $query->where(function (Builder $subQuery) {
                $subQuery->where('type', 'like', '%task%')
                    ->orWhere('title', 'like', '%task%')
                    ->orWhere('link_type', 'tasks');
            }),
            'followup', 'followups', 'follow-up' => $query->where(function (Builder $subQuery) {
                $subQuery->where('type', 'like', '%follow%')
                    ->orWhere('title', 'like', '%follow%')
                    ->orWhere('link_type', 'followups');
            }),
            'amc' => $query->where(function (Builder $subQuery) {
                $subQuery->where('type', 'like', '%amc%')
                    ->orWhere('title', 'like', '%amc%');
            }),
            'team', 'user', 'users' => $query->where(function (Builder $subQuery) {
                $subQuery->where('type', 'like', '%team%')
                    ->orWhere('type', 'like', '%user%')
                    ->orWhere('type', 'like', '%salesperson%')
                    ->orWhere('link_type', 'team');
            }),
            'general', 'notification' => $query->where(function (Builder $subQuery) {
                $subQuery->whereIn('type', ['notification', 'general'])
                    ->orWhere(function (Builder $generalSubQuery) {
                        $generalSubQuery->where('type', 'not like', '%password%')
                            ->where('type', 'not like', '%payment%')
                            ->where('type', 'not like', '%milestone%')
                            ->where('type', 'not like', '%project%')
                            ->where('type', 'not like', '%customer%')
                            ->where('type', 'not like', '%task%')
                            ->where('type', 'not like', '%team%')
                            ->where('type', 'not like', '%follow%')
                            ->where('type', 'not like', '%amc%')
                            ->where('type', 'not like', '%overdue%');
                    });
            }),
            default => $query->where(function (Builder $subQuery) use ($type) {
                $subQuery->where('type', $type)
                    ->orWhere('type', 'like', "%{$type}%")
                    ->orWhere('title', 'like', "%{$type}%");
            }),
        };
    }

    /**
     * Resolves the normalized category name.
     */
    public function getCategoryAttribute(): string
    {
        $type = strtolower($this->type ?? '');
        $title = strtolower($this->title ?? '');

        if (str_contains($type, 'password') || str_contains($type, 'locked') || str_contains($type, 'auth') || str_contains($title, 'password')) {
            return 'password_reset';
        }
        if (str_contains($type, 'overdue') || str_contains($title, 'overdue')) {
            return 'overdue';
        }
        if (str_contains($type, 'payment') || str_contains($type, 'schedule') || str_contains($title, 'payment')) {
            return 'payment';
        }
        if (str_contains($type, 'milestone') || str_contains($title, 'milestone')) {
            return 'milestone';
        }
        if (str_contains($type, 'amc') || str_contains($title, 'amc')) {
            return 'amc';
        }
        if (str_contains($type, 'followup') || str_contains($type, 'follow-up') || str_contains($title, 'follow-up')) {
            return 'followup';
        }
        if (str_contains($type, 'task') || str_contains($title, 'task')) {
            return 'task';
        }
        if (str_contains($type, 'customer') || str_contains($title, 'customer')) {
            return 'customer';
        }
        if (str_contains($type, 'project') || str_contains($type, 'handover') || str_contains($title, 'project')) {
            return 'project';
        }
        if (str_contains($type, 'team') || str_contains($type, 'user') || str_contains($type, 'salesperson')) {
            return 'team';
        }

        return 'general';
    }

    /**
     * Presentation metadata (color, icon key, badge text).
     */
    public function getKindConfigAttribute(): array
    {
        $cat = $this->category;
        $type = strtolower($this->type ?? '');
        $title = strtolower($this->title ?? '');

        if ($cat === 'password_reset') {
            $isLocked = str_contains($type, 'locked') || str_contains($title, 'locked');

            return [
                'color' => $isLocked ? '#d94b4b' : '#7a5af8',
                'icon' => 'lock',
                'badge' => $isLocked ? 'Account Security' : 'Password Reset',
            ];
        }

        if ($cat === 'overdue') {
            return [
                'color' => '#d94b4b',
                'icon' => 'alert',
                'badge' => 'Overdue Alert',
            ];
        }

        if ($cat === 'payment') {
            $isDue = str_contains($type, 'due') || str_contains($title, 'due');

            return [
                'color' => $isDue ? '#e0a13a' : '#5aa236',
                'icon' => $isDue ? 'clock' : 'cash',
                'badge' => $isDue ? 'Payment Due' : 'Payment Received',
            ];
        }

        if ($cat === 'milestone') {
            return [
                'color' => '#0284c7',
                'icon' => 'milestone',
                'badge' => 'Milestone',
            ];
        }

        if ($cat === 'amc') {
            return [
                'color' => '#5aa236',
                'icon' => 'refresh',
                'badge' => 'AMC Renewal',
            ];
        }

        if ($cat === 'followup') {
            return [
                'color' => '#0b3d91',
                'icon' => 'chat',
                'badge' => 'Follow-up Call',
            ];
        }

        if ($cat === 'task') {
            return [
                'color' => '#8b5cf6',
                'icon' => 'task',
                'badge' => 'Task',
            ];
        }

        if ($cat === 'customer') {
            $isRejected = str_contains($type, 'rejected') || str_contains($title, 'rejected');

            return [
                'color' => $isRejected ? '#d94b4b' : '#0b3d91',
                'icon' => 'cust',
                'badge' => $isRejected ? 'Customer Rejected' : 'Customer Event',
            ];
        }

        if ($cat === 'project') {
            $isRejected = str_contains($type, 'rejected') || str_contains($title, 'rejected');
            $isAlert = str_contains($type, 'health') || str_contains($title, 'health') || str_contains($type, 'delayed');

            return [
                'color' => $isRejected ? '#d94b4b' : ($isAlert ? '#e0a13a' : '#2e75b6'),
                'icon' => $type === 'handover' ? 'flag' : 'proj',
                'badge' => $type === 'handover' ? 'Handover' : ($isRejected ? 'Project Rejected' : 'Project Update'),
            ];
        }

        if ($cat === 'team') {
            return [
                'color' => '#475569',
                'icon' => 'users',
                'badge' => 'Team & Staff',
            ];
        }

        return [
            'color' => '#8894ac',
            'icon' => 'bell',
            'badge' => 'System Notification',
        ];
    }

    /**
     * Determines if the given user has permission and downline access to navigate
     * directly to the linked entity (Project, Customer, Settings, etc.).
     */
    public function canAccessTarget(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return false;
        }

        if (empty($this->link_type) || ! $this->link_id) {
            return false;
        }

        if ($this->link_type === 'project') {
            if (! $user->hasPermission('projects', 'view')) {
                return false;
            }

            $project = Project::find($this->link_id);
            if (! $project) {
                return false;
            }

            if ($user->role === 'Admin') {
                return true;
            }

            if ($user->role === 'Manager') {
                $downlineIds = $user->getDownlineUserIds();

                return ($project->coordinator_id && in_array($project->coordinator_id, $downlineIds, true))
                    || ($project->sales_person_id && in_array($project->sales_person_id, $downlineIds, true))
                    || ($project->tech_person_id && in_array($project->tech_person_id, $downlineIds, true));
            }

            if ($user->role === 'Sales') {
                return (int) $project->sales_person_id === (int) $user->id;
            }

            if ($user->isTechPerson()) {
                return (int) $project->tech_person_id === (int) $user->id;
            }

            return (int) $project->coordinator_id === (int) $user->id;
        }

        if ($this->link_type === 'customer') {
            if ($user->isTechPerson() || ! $user->hasPermission('customers', 'view')) {
                return false;
            }

            $customer = Customer::find($this->link_id);
            if (! $customer) {
                return false;
            }

            if ($user->role === 'Admin') {
                return true;
            }

            return Customer::query()->forUser($user)->where('id', $customer->id)->exists();
        }

        if ($this->link_type === 'settings') {
            return $user->hasPermission('settings', 'view') || $user->role === 'Admin';
        }

        if ($this->link_type === 'team') {
            return in_array($user->role, ['Admin', 'Manager'], true);
        }

        if ($this->link_type === 'tasks') {
            return $user->hasPermission('tasks', 'view');
        }

        if ($this->link_type === 'payments') {
            return in_array($user->role, ['Admin', 'Manager', 'Coordinator'], true)
                && ! $user->isTechPerson()
                && $user->hasPermission('payments', 'view');
        }

        if ($this->link_type === 'followups') {
            return $user->hasPermission('followups', 'view');
        }

        return false;
    }
}
