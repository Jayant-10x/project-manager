<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Milestone extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'due_date' => 'date',
        'done_on' => 'date',
        'done' => 'boolean',
        'sort_order' => 'integer',
        'next_chase_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
