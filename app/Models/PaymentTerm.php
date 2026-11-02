<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTerm extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'float',
        'percent' => 'float',
        'received' => 'float',
        'tds' => 'float',
        'grace_days' => 'integer',
        'seq' => 'integer',
    ];

    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        return $query->whereHas('project', fn (Builder $q) => $q->forUser($user));
    }

    public function setDueDateAttribute($value): void
    {
        $this->attributes['due_date'] = ! empty($value) ? $value : null;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'term_id');
    }

    public function getBalanceAttribute(): float
    {
        return max(0.0, (float) $this->amount - (float) $this->received - (float) $this->tds);
    }
}
