<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model {
    protected $primaryKey = 'k';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    public $timestamps = false;

    public static function getValue(string $key, mixed $default = null): mixed {
        $record = static::query()->where('k', $key)->first();
        return $record ? $record->v : $default;
    }
}
