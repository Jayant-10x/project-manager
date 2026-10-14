<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model {
    protected $fillable = [
        'user_email', 'type', 'title', 'body', 'link_type', 'link_id', 'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}
