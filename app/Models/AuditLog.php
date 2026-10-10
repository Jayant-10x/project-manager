<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model {
    protected $table = 'audit_log';
    public $timestamps = false;

    protected $fillable = [
        'who', 'action', 'entity', 'entity_id', 'entity_code', 'detail', 'at_time'
    ];

    protected $casts = [
        'at_time' => 'datetime',
    ];
}
