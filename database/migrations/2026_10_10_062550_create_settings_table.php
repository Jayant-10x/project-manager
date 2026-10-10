<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('k', 60)->primary();
            $table->text('v')->nullable();
        });
    }

    public function down(): void {
        Schema::dropIfExists('settings');
    }
};
