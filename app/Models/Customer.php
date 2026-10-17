<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $guarded = ['id'];

    public function getCodeAttribute(): string
    {
        return $this->customer_code ?? '';
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_person_id')->where('role', 'Sales');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class)->orderByDesc('happened_on');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
