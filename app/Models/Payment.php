<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model {
    protected $fillable = [
        'code', 'project_id', 'customer_id', 'term_id', 'amount', 'tds',
        'paid_on', 'mode', 'reference', 'bank', 'note', 'entered_by'
    ];

    protected $casts = [
        'amount' => 'float',
        'tds' => 'float',
        'paid_on' => 'date',
    ];

    public function project(): BelongsTo {
        return $this->belongsTo(Project::class);
    }

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

    public function paymentTerm(): BelongsTo {
        return $this->belongsTo(PaymentTerm::class, 'term_id');
    }
}
