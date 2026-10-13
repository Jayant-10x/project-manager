<?php

namespace App\Models;

use App\Services\MoneyCalculationService;
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
        'value' => 'float',
        'cost' => 'float',
        'amc_amount' => 'float',
        'amc_required' => 'boolean',
        'handover_done' => 'boolean',
    ];

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

    public function coordinator(): BelongsTo {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function createdBy(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function terms(): HasMany {
        return $this->hasMany(PaymentTerm::class)->orderBy('seq');
    }

    public function milestones(): HasMany {
        return $this->hasMany(Milestone::class)->orderBy('sort_order');
    }

    public function payments(): HasMany {
        return $this->hasMany(Payment::class)->orderByDesc('paid_on');
    }

    public function documents(): HasMany {
        return $this->hasMany(Document::class)->orderByDesc('id');
    }

    public function tasks(): HasMany {
        return $this->hasMany(Task::class);
    }

    /**
     * Strict assignment scope: Coordinators only query projects assigned to them.
     */
    public function scopeForUser(Builder $query, ?User $user = null): Builder {
        $user = $user ?? auth()->user();
        if ($user && $user->role !== 'Admin') {
            return $query->where('coordinator_id', $user->id);
        }
        return $query;
    }

    /**
     * Calculated financial breakdown cache for the view.
     */
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
