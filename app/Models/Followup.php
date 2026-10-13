<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Followup extends Model {
    protected $guarded = ['id'];

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

    public function createdBy(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
