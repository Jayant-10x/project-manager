<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email', 160)->unique();
            $table->string('phone', 30)->nullable();
            $table->enum('role', ['Admin', 'Manager', 'Coordinator', 'Sales'])->default('Coordinator');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->string('designation', 60)->nullable();
            $table->string('city', 60)->nullable();

            // Presence tracking
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen')->nullable();

            // Auth & Account Security
            $table->string('password');
            $table->rememberToken(); // Standard Laravel session persistence
            $table->boolean('must_change')->default(false);
            $table->unsignedSmallInteger('fail_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            // Hierarchy link
            $table->foreignId('reporting_to_id')->nullable()->constrained('users')->nullOnDelete();

            // Self-referencing audit relation
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('users');
    }
};
