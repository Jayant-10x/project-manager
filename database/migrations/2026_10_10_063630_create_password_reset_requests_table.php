<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['Pending', 'Resolved', 'Cancelled'])->default('Pending');
            $table->string('ip_address', 45)->nullable()->default(null);
            $table->foreignId('resolved_by')->nullable()->default(null)->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->default(null);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_requests');
    }
};
