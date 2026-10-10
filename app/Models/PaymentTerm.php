<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTerm extends Model {
    protected $fillable = [
        'project_id', 'seq', 'label', 'amount', 'percent', 'trigger_type',
        'milestone_id', 'due_date', 'grace_days', 'status', 'received',
        'tds', 'notes'
    ];

    protected $casts = [
        'amount' => 'float',
        'percent' => 'float',
        'received' => 'float',
        'tds' => 'float',
        'due_date' => 'date',
    ];

    public function project(): BelongsTo {
        return $this->belongsTo(Project::class);
    }

    public function milestone(): BelongsTo {
        return $this->belongsTo(Milestone::class);
    }

    public function payments(): HasMany {
        return $this->hasMany(Payment::class, 'term_id');
    }
}
