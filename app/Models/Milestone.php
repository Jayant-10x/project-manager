<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Milestone extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'due_date' => 'date',
        'done_on' => 'date',
        'done' => 'boolean',
        'sort_order' => 'integer',
        'next_chase_date' => 'date',
    ];

    public function setDueDateAttribute($value): void
    {
        $this->attributes['due_date'] = ! empty($value) ? $value : null;
    }

    public function setDoneOnAttribute($value): void
    {
        $this->attributes['done_on'] = ! empty($value) ? $value : null;
    }

    public function setNextChaseDateAttribute($value): void
    {
        $this->attributes['next_chase_date'] = ! empty($value) ? $value : null;
    }

    public function setChaseNotesAttribute($value): void
    {
        $this->attributes['chase_notes'] = ! empty($value) ? $value : null;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function paymentTerms(): HasMany
    {
        return $this->hasMany(PaymentTerm::class, 'milestone_id');
    }

    public function isLocked(): bool
    {
        return $this->paymentTerms()->exists();
    }

    public function getTitleAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }
}
