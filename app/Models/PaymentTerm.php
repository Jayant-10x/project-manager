<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }
}
