<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CodeGeneratorService
{
    /**
     * Entity mapping defining table and code column for known counter names.
     *
     * @var array<string, array{table: string, column: string}>
     */
    protected array $entityMap = [
        'project' => ['table' => 'projects', 'column' => 'project_code'],
        'payment' => ['table' => 'payments', 'column' => 'code'],
        'customer' => ['table' => 'customers', 'column' => 'customer_code'],
    ];

    /**
     * Generates a sequential, monotonic unique code for an entity.
     * Reconciles with existing database records to prevent collisions with seeded or imported data.
     */
    public function generate(string $name, string $prefix = ''): string
    {
        return DB::transaction(function () use ($name, $prefix) {
            $counter = DB::table('counters')->where('name', $name)->lockForUpdate()->first();
            $currentVal = $counter ? (int) $counter->value : 0;

            if (isset($this->entityMap[$name])) {
                $table = $this->entityMap[$name]['table'];
                $column = $this->entityMap[$name]['column'];

                // Self-healing: Ensure counter value is at least the highest code in the table
                $maxExisting = $this->getMaxNumericCode($table, $column);
                if ($maxExisting > $currentVal) {
                    $currentVal = $maxExisting;
                }
            }

            $nextVal = $currentVal + 1;

            // Ensure candidate code does not collide with an existing row
            if (isset($this->entityMap[$name])) {
                $table = $this->entityMap[$name]['table'];
                $column = $this->entityMap[$name]['column'];
                while (DB::table($table)->where($column, $prefix.str_pad((string) $nextVal, 4, '0', STR_PAD_LEFT))->exists()) {
                    $nextVal++;
                }
            }

            DB::table('counters')->updateOrInsert(
                ['name' => $name],
                ['value' => $nextVal]
            );

            return $prefix.str_pad((string) $nextVal, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Scans a table column for numeric sequences and resolves the maximum integer value.
     */
    public function getMaxNumericCode(string $table, string $column): int
    {
        $existingCodes = DB::table($table)->whereNotNull($column)->pluck($column);
        $max = 0;

        foreach ($existingCodes as $existingCode) {
            $cleaned = preg_replace('/[^0-9]/', '', (string) $existingCode);
            if ($cleaned !== '') {
                $val = (int) $cleaned;
                if ($val > $max) {
                    $max = $val;
                }
            }
        }

        return $max;
    }

    /**
     * Synchronizes a specific counter with the highest numeric sequence present in its table.
     */
    public function sync(string $name): int
    {
        if (! isset($this->entityMap[$name])) {
            return 0;
        }

        $table = $this->entityMap[$name]['table'];
        $column = $this->entityMap[$name]['column'];
        $max = $this->getMaxNumericCode($table, $column);

        DB::table('counters')->updateOrInsert(
            ['name' => $name],
            ['value' => $max]
        );

        return $max;
    }

    /**
     * Synchronizes all registered entity counters with the existing database data.
     *
     * @return array<string, int>
     */
    public function syncAll(): array
    {
        $synced = [];
        foreach (array_keys($this->entityMap) as $name) {
            $synced[$name] = $this->sync($name);
        }

        return $synced;
    }
}
