<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model {
    protected $fillable = [
        'code', 'company', 'contact_name', 'email', 'phone', 'alt_phone',
        'city', 'state', 'address', 'gstin', 'pan', 'billing_name',
        'billing_addr', 'source', 'industry', 'onboarded_by', 'status',
        'internal_notes', 'created_by'
    ];

    public function projects(): HasMany {
        return $this->hasMany(Project::class);
    }

    public function payments(): HasMany {
        return $this->hasMany(Payment::class);
    }

    public function documents(): HasMany {
        return $this->hasMany(Document::class);
    }

    public function followups(): HasMany {
        return $this->hasMany(Followup::class);
    }

    public function tasks(): HasMany {
        return $this->hasMany(Task::class);
    }
}
