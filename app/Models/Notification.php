<?php

namespace App\Models;

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
