<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $guarded = ['id'];

    public function getCodeAttribute(): string
    {
        return $this->project_code ?? '';
    }

    protected $casts = [
        'start_date' => 'date',
        'delivery_date' => 'date',
        'actual_end' => 'date',
        'amc_start' => 'date',
        'amc_end' => 'date',
        'amc_required' => 'boolean',
        'handover_done' => 'boolean',
        'value' => 'decimal:2',
        'cost' => 'decimal:2',
        'amc_amount' => 'decimal:2',
        'renew_alert_days' => 'integer',
    ];

    /**
     * Local query scope for role-based coordinator data isolation.
     */
    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query;
        }

        if ($user->role === 'Admin') {
            return $query;
        }

        if ($user->role === 'Manager') {
            $downlineIds = $user->getDownlineUserIds();

            return $query->where(function ($subQuery) use ($downlineIds) {
                $subQuery->whereIn('coordinator_id', $downlineIds)
                    ->orWhereIn('sales_person_id', $downlineIds);
            });
        }

        if ($user->role === 'Sales') {
            return $query->where('sales_person_id', $user->id);
        }

        return $query->where('coordinator_id', $user->id);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_person_id')->where('role', 'Sales');
    }

    public function terms(): HasMany
    {
        return $this->hasMany(PaymentTerm::class)->orderBy('seq');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('paid_on');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('sort_order');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->orderByDesc('id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function getMoneyAttribute(): array
    {
        $scheduled = (float) $this->terms->where('status', '!=', 'Cancelled')->sum('amount');
        $cash = (float) $this->payments->sum('amount');
        $tds = (float) $this->payments->sum('tds');
        $settled = round($cash + $tds, 2);
        $due = round(max(0, $scheduled - $cash - $tds), 2);
        $overdue = (float) $this->terms->where('status', 'Overdue')->sum(fn ($term) => max(0, $term->amount - $term->received - $term->tds));

        return [
            'scheduled' => $scheduled,
            'received' => $settled, // Option B: Gross Realized Revenue (Cash Received + TDS Deducted)
            'settled' => $settled,
            'cash' => $cash,
            'tds' => $tds,
            'due' => $due,
            'overdue' => $overdue,
        ];
    }
}
