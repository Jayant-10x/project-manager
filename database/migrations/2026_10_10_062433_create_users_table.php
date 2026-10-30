<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email', 160)->unique('email');
            $table->timestamp('email_verified_at')->nullable()->default(null);
            $table->string('phone', 30)->nullable()->default(null)->unique('phone');
            $table->enum('role', ['Admin', 'Manager', 'Coordinator', 'Sales', 'TechPerson'])->default('Sales');
            $table->enum('status', ['Active', 'Inactive'])->default('Inactive');
            $table->string('designation', 60)->nullable()->default(null);
            $table->string('city', 60)->nullable()->default(null);
            $table->string('avatar')->nullable()->default(null);
            $table->decimal('target', 14, 2)->default(0.00);
            $table->json('permissions')->nullable()->default(null);

            // Presence tracking
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen')->nullable()->default(null);

            // Auth & Account Security
            $table->string('password');
            $table->rememberToken(); // Standard Laravel session persistence
            $table->boolean('must_change')->default(false);
            $table->unsignedSmallInteger('fail_count')->default(0);
            $table->timestamp('locked_until')->nullable()->default(null);
            // Hierarchy link
            $table->foreignId('reporting_to')->nullable()->default(null)->constrained('users', indexName: 'reporting_to')->nullOnDelete()->cascadeOnUpdate();

            // Self-referencing audit relation
            $table->foreignId('created_by')->nullable()->default(null)->constrained('users', indexName: 'created_by')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
