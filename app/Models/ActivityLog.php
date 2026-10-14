<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model {
    protected $table = 'activity_logs';
    public $timestamps = false;

    protected $fillable = [
        'who', 'action', 'entity', 'entity_id', 'entity_code', 'detail', 'at_time'
    ];

    protected $casts = [
        'at_time' => 'datetime',
    ];
}
