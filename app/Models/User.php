<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'pass_hash',
        'token',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'token_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_seen' => 'datetime',
        'joined' => 'date',
        'online' => 'boolean',
        'must_change' => 'boolean',
    ];

    /**
     * Accommodates both standard 'password' and legacy 'pass_hash' columns.
     */
    public function getAuthPassword(): string {
        return $this->pass_hash ?? $this->attributes['password'] ?? '';
    }

    public function setPasswordAttribute($value): void {
        $hashed = Hash::needsRehash($value) ? Hash::make($value) : $value;
        $this->attributes['password'] = $hashed;
        $this->attributes['pass_hash'] = $hashed;
    }

    public function isAdmin(): bool {
        return ($this->role ?? '') === 'Admin';
    }

    public function projects(): HasMany {
        return $this->hasMany(Project::class, 'coordinator_id');
    }

    public function tasks(): HasMany {
        return $this->hasMany(Task::class, 'assigned_to_id');
    }
}
