<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model {
    protected $fillable = [
        'code', 'customer_id', 'name', 'description', 'project_type',
        'coordinator', 'value', 'cost', 'start_date', 'delivery_date',
        'actual_end', 'status', 'health', 'delay_reason', 'amc_required',
        'amc_start', 'amc_end', 'amc_amount', 'renew_alert_days',
        'handover_done', 'handover_notes', 'notes', 'created_by'
    ];

    protected $casts = [
        'value' => 'float',
        'cost' => 'float',
        'amc_amount' => 'float',
        'amc_required' => 'boolean',
        'handover_done' => 'boolean',
        'start_date' => 'date',
        'delivery_date' => 'date',
        'actual_end' => 'date',
        'amc_start' => 'date',
        'amc_end' => 'date',
    ];

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

    public function paymentTerms(): HasMany {
        return $this->hasMany(PaymentTerm::class);
    }

    public function payments(): HasMany {
        return $this->hasMany(Payment::class);
    }

    public function milestones(): HasMany {
        return $this->hasMany(Milestone::class);
    }

    public function documents(): HasMany {
        return $this->hasMany(Document::class);
    }

    public function tasks(): HasMany {
        return $this->hasMany(Task::class);
    }
}
