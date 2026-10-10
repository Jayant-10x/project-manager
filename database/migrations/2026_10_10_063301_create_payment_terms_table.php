<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedSmallInteger('seq')->default(1);
            $table->string('label', 120);
            $table->decimal('amount', 14, 2)->default(0.00);
            $table->decimal('percent', 6, 2)->default(0.00);
            $table->enum('trigger_type', ['fixed', 'milestone', 'recurring'])->default('fixed');
            $table->foreignId('milestone_id')->nullable()->constrained('milestones')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('grace_days')->default(0);
            $table->enum('status', ['Pending', 'Partial', 'Paid', 'Overdue', 'Cancelled'])->default('Pending');
            $table->decimal('received', 14, 2)->default(0.00);
            $table->decimal('tds', 14, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('payment_terms');
    }
};
