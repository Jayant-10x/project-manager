<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->default(null)->constrained('projects')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->default(null)->constrained('customers')->cascadeOnDelete();
            $table->string('title', 200);
            $table->date('due_date')->nullable()->default(null);
            $table->boolean('done')->default(false);
            $table->enum('priority', ['Low', 'Medium', 'High', 'Urgent'])->default('Medium');
            $table->foreignId('assigned_to')->nullable()->default(null)->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->default(null)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
