<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable {
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'role', 'designation', 'city', 'status',
        'online', 'last_seen', 'joined', 'pass_hash', 'must_change',
        'token', 'token_at', 'reset_hash', 'reset_expires', 'reset_used',
        'fail_count', 'locked_until'
    ];

    protected $hidden = [
        'pass_hash', 'token', 'reset_hash'
    ];

    protected $casts = [
        'online' => 'boolean',
        'must_change' => 'boolean',
        'reset_used' => 'boolean',
        'last_seen' => 'datetime',
        'token_at' => 'datetime',
        'reset_expires' => 'datetime',
        'locked_until' => 'datetime',
        'joined' => 'date',
    ];

    public function getAuthPassword(): string {
        return $this->pass_hash;
    }

    public function isAdmin(): bool {
        return $this->role === 'Admin';
    }

    public function isCoordinator(): bool {
        return in_array($this->role, ['Admin', 'Coordinator'], true);
    }
}
