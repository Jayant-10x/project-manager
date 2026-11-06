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

        // Prevent negative values from breaking alert day calculations if default is positive
        if (str_ends_with($key, '_alert_days') && is_numeric($val) && (int) $val < 0 && is_numeric($default) && $default > 0) {
            return $default;
        }

        return $val;
    }

    /**
     * Get configured payment reminder days sorted in descending order (e.g. [7, 3, 1, 0]).
     *
     * @return array<int>
     */
    public static function getPaymentReminderDays(): array
    {
        $raw = static::getValue('payment_alert_days', [7, 3, 1]);

        if (is_array($raw)) {
            $days = $raw;
        } elseif (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $days = $decoded;
            } elseif (str_contains($raw, ',')) {
                $days = explode(',', $raw);
            } elseif (is_numeric($raw)) {
                $days = [(int) $raw];
            } else {
                $days = [7, 3, 1];
            }
        } elseif (is_numeric($raw)) {
            $days = [(int) $raw];
        } else {
            $days = [7, 3, 1];
        }

        $clean = array_values(array_unique(array_filter(
            array_map('intval', $days),
            fn ($dayValue) => $dayValue >= 0 && $dayValue <= 180
        )));

        rsort($clean);

        return ! empty($clean) ? $clean : [7, 3, 1];
    }
}
