<?php

namespace App\Models;

use Carbon\Carbon;
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

    public function recalculateStatus(): void
    {
        $remPaid = (float) $this->payments()->sum('amount');
        $remTds = (float) $this->payments()->sum('tds');
        $this->received = $remPaid;
        $this->tds = $remTds;

        if (($remPaid + $remTds) >= ($this->amount - 0.01)) {
            $this->status = 'Paid';
        } elseif ($remPaid > 0) {
            $this->status = 'Partial';
        } else {
            $dueDate = $this->due_date ? Carbon::parse($this->due_date) : null;
            $limit = $dueDate ? $dueDate->copy()->addDays($this->grace_days ?? 0)->toDateString() : null;
            $this->status = ($limit && $limit < now()->toDateString()) ? 'Overdue' : 'Pending';
        }

        $this->save();
    }
}
