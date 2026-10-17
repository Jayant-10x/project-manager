<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_persons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('phone', 30)->nullable()->default(null);
            $table->string('email', 160)->nullable()->default(null);
            $table->decimal('target', 14, 2)->default(0.00);
            $table->enum('status', ['Active', 'Inactive'])->default('Inactive');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_persons');
    }
};
