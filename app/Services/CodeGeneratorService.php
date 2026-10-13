<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CodeGeneratorService {
    public function generate(string $name, string $prefix = ''): string {
        return DB::transaction(function () use ($name, $prefix) {
            $counter = DB::table('counters')->where('name', $name)->lockForUpdate()->first();

            if (!$counter) {
                DB::table('counters')->insert(['name' => $name, 'value' => 1]);
                $val = 1;
            } else {
                $val = $counter->value + 1;
                DB::table('counters')->where('name', $name)->update(['value' => $val]);
            }

            return $prefix . str_pad((string)$val, 4, '0', STR_PAD_LEFT);
        });
    }
}
