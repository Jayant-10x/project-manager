<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable {
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
    ];

    /**
     * Accommodates both standard 'password' and legacy 'pass_hash' columns.
     */
    public function getAuthPassword(): string {
        return $this->pass_hash ?? $this->attributes['password'] ?? '';
    }

    public function setPasswordAttribute($value): void {
        $this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
    }

    public function isAdmin(): bool {
        return ($this->role ?? '') === 'Admin';
    }

    public function projects(): HasMany {
        return $this->hasMany(Project::class, 'coordinator_id');
    }

    public function tasks(): HasMany {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reportingTo(): BelongsTo {
        return $this->belongsTo(User::class, 'reporting_to');
    }

    public function subordinates(): HasMany {
        return $this->hasMany(User::class, 'reporting_to');
    }

    public function salesProjects(): HasMany {
        return $this->hasMany(Project::class, 'sales_person_id');
    }

    /**
     * Resolves all subordinate user IDs recursively (including self).
     */
    public function getDownlineUserIds(): array {
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
}
