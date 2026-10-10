<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('project_type', 60)->nullable();
            $table->string('coordinator', 80)->nullable();
            $table->decimal('value', 14, 2)->default(0.00);
            $table->decimal('cost', 14, 2)->default(0.00);
            $table->date('start_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->date('actual_end')->nullable();
            $table->enum('status', ['Onboarding', 'In Progress', 'On Hold', 'Delivered', 'Cancelled'])->default('Onboarding');
            $table->enum('health', ['On Track', 'At Risk', 'Delayed', 'Completed'])->default('On Track');
            $table->string('delay_reason', 200)->nullable();
            $table->boolean('amc_required')->default(false);
            $table->date('amc_start')->nullable();
            $table->date('amc_end')->nullable();
            $table->decimal('amc_amount', 14, 2)->default(0.00);
            $table->unsignedSmallInteger('renew_alert_days')->default(7);
            $table->boolean('handover_done')->default(false);
            $table->text('handover_notes')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by', 80)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('projects');
    }
};
