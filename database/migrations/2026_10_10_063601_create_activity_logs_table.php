<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->default(null)->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->string('entity', 40);
            $table->unsignedBigInteger('entity_id')->nullable()->default(null);
            $table->string('entity_code', 20)->nullable()->default(null);
            $table->text('detail')->nullable()->default(null);
            $table->json('changes')->nullable()->default(null);
            $table->timestamp('at_time')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
