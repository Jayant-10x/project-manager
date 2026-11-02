<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'paid_on' => 'date',
        'amount' => 'float',
        'tds' => 'float',
    ];

    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        return $query->whereHas('project', fn (Builder $q) => $q->forUser($user));
    }

    public function setPaidOnAttribute($value): void
    {
        $this->attributes['paid_on'] = ! empty($value) ? $value : null;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class, 'term_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
