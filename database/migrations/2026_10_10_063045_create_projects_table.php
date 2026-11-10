<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code', 20)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('name', 160);
            $table->text('description')->nullable()->default(null);
            $table->string('project_type', 60)->nullable()->default(null);
            $table->decimal('value', 14, 2)->default(0.00);
            $table->decimal('cost', 14, 2)->default(0.00);
            $table->date('start_date')->nullable()->default(null);
            $table->date('delivery_date')->nullable()->default(null);
            $table->date('actual_end')->nullable()->default(null);
            $table->enum('status', ['Onboarding', 'In Progress', 'On Hold', 'Delivered', 'Cancelled'])->default('Onboarding');
            $table->enum('approval_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->string('rejection_reason', 255)->nullable()->default(null);
            $table->enum('health', ['On Track', 'At Risk', 'Delayed', 'Completed'])->default('On Track');
            $table->string('delay_reason', 200)->nullable()->default(null);
            $table->boolean('amc_required')->default(false);
            $table->date('amc_start')->nullable()->default(null);
            $table->date('amc_end')->nullable()->default(null);
            $table->decimal('amc_amount', 14, 2)->default(0.00);
            $table->unsignedSmallInteger('renew_alert_days')->default(7);
            $table->boolean('handover_done')->default(false);
            $table->text('handover_notes')->nullable()->default(null);
            $table->text('notes')->nullable()->default(null);
            $table->foreignId('coordinator_id')->nullable()->default(null)->constrained('users')->nullOnDelete();
            $table->foreignId('sales_person_id')->nullable()->default(null)->constrained('users')->nullOnDelete();
            $table->foreignId('tech_person_id')->nullable()->default(null)->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->default(null)->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->default(null)->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->default(null);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
