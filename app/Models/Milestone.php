<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Milestone extends Model {
    protected $fillable = [
        'project_id', 'name', 'due_date', 'done', 'done_on', 'status',
        'sort_order', 'notes'
    ];

    protected $casts = [
        'done' => 'boolean',
        'due_date' => 'date',
        'done_on' => 'date',
    ];

    public function project(): BelongsTo {
        return $this->belongsTo(Project::class);
    }
}
