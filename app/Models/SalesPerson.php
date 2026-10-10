<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPerson extends Model {
    protected $table = 'sales_persons';

    protected $fillable = [
        'name', 'phone', 'email', 'target', 'status'
    ];

    protected $casts = [
        'target' => 'float',
    ];
}
