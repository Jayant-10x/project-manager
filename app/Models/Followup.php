<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Followup extends Model {
    protected $fillable = [
        'customer_id', 'project_id', 'kind', 'summary', 'detail',
        'happened_on', 'next_date', 'next_note', 'created_by'
    ];

    protected $casts = [
        'happened_on' => 'datetime',
        'next_date' => 'date',
    ];

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

    public function project(): BelongsTo {
        return $this->belongsTo(Project::class);
    }
}
