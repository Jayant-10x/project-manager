<?php

namespace App\Services;

use App\Models\Counter;
use Illuminate\Support\Facades\DB;

class CodeGeneratorService {
    public function generate(string $name, string $prefix, int $padLength = 4): string {
        return DB::transaction(function () use ($name, $prefix, $padLength) {
            DB::statement('INSERT IGNORE INTO counters (name, value) VALUES (?, 0)', [$name]);
            DB::statement('UPDATE counters SET value = value + 1 WHERE name = ?', [$name]);

            $counter = Counter::query()->where('name', $name)->lockForUpdate()->first();
            $nextNumber = $counter ? (int)$counter->value : 1;

            return $prefix . str_pad((string)$nextNumber, $padLength, '0', STR_PAD_LEFT);
        });
    }
}
