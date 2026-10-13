<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model {
    protected $guarded = ['id'];

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
    public function scopeForUser(Builder $query, ?User $user = null): Builder {
        $user = $user ?? auth()->user();

        // If the user is not an Admin, restrict to assigned projects
        if ($user && $user->role !== 'Admin') {
            return $query->where('coordinator_id', $user->id);
        }

        return $query;
    }

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

    public function coordinator(): BelongsTo {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function terms(): HasMany {
        return $this->hasMany(PaymentTerm::class)->orderBy('seq');
    }

    public function payments(): HasMany {
        return $this->hasMany(Payment::class)->orderByDesc('paid_on');
    }

    public function milestones(): HasMany {
        return $this->hasMany(Milestone::class)->orderBy('sort_order');
    }

    public function documents(): HasMany {
        return $this->hasMany(Document::class)->orderByDesc('id');
    }

    public function tasks(): HasMany {
        return $this->hasMany(Task::class);
    }

    public function getMoneyAttribute(): array {
        $scheduled = (float)$this->terms->where('status', '!=', 'Cancelled')->sum('amount');
        $received = (float)$this->payments->sum('amount');
        $tds = (float)$this->payments->sum('tds');
        $overdue = (float)$this->terms->where('status', 'Overdue')->sum(fn($t) => max(0, $t->amount - $t->received - $t->tds));

        return [
            'scheduled' => $scheduled,
            'received' => $received,
            'tds' => $tds,
            'due' => round(max(0, $scheduled - $received - $tds), 2),
            'overdue' => $overdue,
        ];
    }
}
