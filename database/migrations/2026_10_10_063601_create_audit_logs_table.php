<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->string('entity', 40);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_code', 20)->nullable();
            $table->text('detail')->nullable();
            $table->timestamp('at_time')->useCurrent();
        });
    }

    public function down(): void {
        Schema::dropIfExists('audit_log');
    }
};
