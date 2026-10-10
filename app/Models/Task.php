<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model {
    protected $fillable = [
        'project_id', 'customer_id', 'title', 'due_date', 'done',
        'priority', 'assigned_to', 'created_by'
    ];

    protected $casts = [
        'done' => 'boolean',
        'due_date' => 'date',
    ];

    public function project(): BelongsTo {
        return $this->belongsTo(Project::class);
    }

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }
}
