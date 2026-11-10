<?php

namespace App\Models;

use App\Constants\Permissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_seen' => 'datetime',
        'is_online' => 'boolean',
        'must_change' => 'boolean',
        'fail_count' => 'integer',
        'target' => 'float',
        'permissions' => 'array',
    ];

    /**
     * Accommodates both standard 'password' and legacy 'pass_hash' columns.
     */
    public function getAuthPassword(): string
    {
        return $this->pass_hash ?? $this->attributes['password'] ?? '';
    }

    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
    }

    public function isAdmin(): bool
    {
        return ($this->role ?? '') === 'Admin';
    }

    public function isTechPerson(): bool
    {
        return ($this->role ?? '') === 'TechPerson';
    }

    public function hasPermission(string $moduleOrAbility, ?string $action = null): bool
    {
        if ($action === null && str_contains($moduleOrAbility, '.')) {
            [$module, $act] = explode('.', $moduleOrAbility, 2);
        } else {
            $module = $moduleOrAbility;
            $act = $action ?? 'view';
        }

        // Check if user has explicit customized permission (allow or deny)
        if (is_array($this->permissions) && isset($this->permissions[$module])) {
            $modulePerms = $this->permissions[$module];
            if ($act === 'add/edit' || $act === 'add_edit') {
                return ! empty($modulePerms['add']) || ! empty($modulePerms['edit']);
            }
            if (array_key_exists($act, $modulePerms)) {
                return (bool) $modulePerms[$act];
            }
        }

        return Permissions::can($this->role, $module, $act);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! empty($this->avatar)) {
            if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
                return $this->avatar;
            }

            return asset('storage/'.$this->avatar);
        }

        return null;
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'coordinator_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reportingTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporting_to');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'reporting_to');
    }

    public function salesProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'sales_person_id');
    }

    public function techProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'tech_person_id');
    }

    /**
     * Total booked deals value for this salesperson.
     */
    public function getSalesAchievedAmountAttribute(): float
    {
        if (array_key_exists('total_sales_value', $this->attributes)) {
            return (float) ($this->attributes['total_sales_value'] ?? 0.0);
        }

        return (float) $this->salesProjects()->sum('value');
    }

    /**
     * Target achievement percentage.
     */
    public function getTargetProgressPercentAttribute(): float
    {
        $target = (float) ($this->target ?? 0.0);
        if ($target <= 0.0) {
            return 0.0;
        }

        return round(($this->sales_achieved_amount / $target) * 100, 1);
    }

    /**
     * Target achievement status styling and label.
     */
    public function getTargetStatusAttribute(): array
    {
        $target = (float) ($this->target ?? 0.0);
        $progress = $this->target_progress_percent;

        if ($target <= 0.0) {
            return [
                'label' => 'No Target',
                'badge' => 'bg-gray-100 text-gray-600 border-gray-200',
                'color' => 'gray',
            ];
        }

        if ($progress >= 100.0) {
            return [
                'label' => 'Target Achieved',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'color' => 'emerald',
            ];
        }

        if ($progress >= 70.0) {
            return [
                'label' => 'On Track',
                'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
                'color' => 'blue',
            ];
        }

        if ($progress >= 40.0) {
            return [
                'label' => 'In Progress',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                'color' => 'amber',
            ];
        }

        return [
            'label' => 'Behind Target',
            'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            'color' => 'rose',
        ];
    }

    /**
     * Resolves all subordinate user IDs recursively (including self).
     */
    public function getDownlineUserIds(): array
    {
        $ids = [$this->id];
        $directSubs = self::where('reporting_to', $this->id)->pluck('id')->toArray();

        foreach ($directSubs as $subId) {
            $subUser = self::find($subId);
            if ($subUser) {
                $ids = array_merge($ids, $subUser->getDownlineUserIds());
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Resolves direct upline supervisor.
     */
    public function getDirectUpline(): ?User
    {
        return $this->reporting_to ? self::find($this->reporting_to) : null;
    }

    /**
     * Resolves all upline user IDs recursively (managers/supervisors in the reporting chain).
     *
     * @param  bool  $includeAdmins  If true, also includes active Admins even if not in direct reporting chain.
     * @return array<int>
     */
    public function getUplineUserIds(bool $includeAdmins = false): array
    {
        $uplineIds = [];
        $current = $this;
        $visited = [$this->id];

        while ($current && $current->reporting_to) {
            $parentId = (int) $current->reporting_to;
            if (in_array($parentId, $visited, true)) {
                break; // Prevent circular hierarchy loops
            }
            $visited[] = $parentId;
            $uplineIds[] = $parentId;
            $current = self::find($parentId);
        }

        if ($includeAdmins) {
            $adminIds = self::where('role', 'Admin')
                ->where('status', 'Active')
                ->where('id', '!=', $this->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach ($adminIds as $adminId) {
                if (! in_array($adminId, $uplineIds, true)) {
                    $uplineIds[] = $adminId;
                }
            }
        }

        return array_values(array_unique($uplineIds));
    }
}
