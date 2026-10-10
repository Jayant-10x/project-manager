<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model {
    protected $fillable = [
        'customer_id', 'project_id', 'category', 'title', 'file_name',
        'stored_as', 'mime', 'size_kb', 'uploaded_by'
    ];

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

    public function project(): BelongsTo {
        return $this->belongsTo(Project::class);
    }
}
