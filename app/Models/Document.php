<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model {
    protected $guarded = ['id'];

    protected $casts = [
        'size_kb' => 'integer',
    ];

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

    public function project(): BelongsTo {
        return $this->belongsTo(Project::class);
    }

    public function uploadedBy(): BelongsTo {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
