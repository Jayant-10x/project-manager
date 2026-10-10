<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model {
    protected $primaryKey = 'k';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['k', 'v'];

    public static function getValue(string $key, mixed $default = null): mixed {
        $setting = static::query()->find($key);
        return $setting ? $setting->v : $default;
    }

    public static function setValue(string $key, mixed $value): void {
        static::query()->updateOrCreate(['k' => $key], ['v' => (string)$value]);
    }
}
