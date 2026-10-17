<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public $timestamps = false;

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $record = static::query()->where('key', $key)->first();

        if (! $record) {
            return $default;
        }

        $val = $record->value ?? $record->v ?? null;

        if ($val === null || $val === '') {
            return $default;
        }

        // Prevent 0 from breaking alert day calculations if default is positive
        if (str_ends_with($key, '_alert_days') && (int) $val <= 0 && is_numeric($default) && $default > 0) {
            return $default;
        }

        return $val;
    }
}
