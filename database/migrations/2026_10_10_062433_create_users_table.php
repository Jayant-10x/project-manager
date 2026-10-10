<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('email', 160)->unique();
            $table->string('phone', 30)->nullable();
            $table->enum('role', ['Admin', 'Coordinator'])->default('Coordinator');
            $table->string('designation', 60)->nullable();
            $table->string('city', 60)->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->boolean('online')->default(false);
            $table->timestamp('last_seen')->nullable();
            $table->date('joined')->nullable();
            $table->string('pass_hash', 255);
            $table->boolean('must_change')->default(false);
            $table->string('token', 80)->nullable()->index();
            $table->timestamp('token_at')->nullable();
            $table->string('reset_hash', 128)->nullable()->index();
            $table->timestamp('reset_expires')->nullable();
            $table->boolean('reset_used')->default(false);
            $table->unsignedSmallInteger('fail_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('users');
    }
};
