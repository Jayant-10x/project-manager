<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->default(null)->constrained('users')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('title', 200);
            $table->string('body', 300);
            $table->string('link_type', 20)->nullable()->default(null);
            $table->unsignedBigInteger('link_id')->nullable()->default(null);
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('notifications');
    }
};
