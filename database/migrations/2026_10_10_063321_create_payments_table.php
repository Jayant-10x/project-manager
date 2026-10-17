<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->default(null)->constrained('payment_terms')->nullOnDelete();
            $table->decimal('amount', 14, 2)->default(0.00);
            $table->decimal('tds', 14, 2)->default(0.00);
            $table->date('paid_on');
            $table->enum('mode', ['Cash', 'UPI', 'NEFT', 'RTGS', 'Cheque', 'Card', 'Other'])->default('NEFT');
            $table->string('reference', 80)->nullable()->default(null);
            $table->string('bank', 80)->nullable()->default(null);
            $table->text('note')->nullable()->default(null);
            $table->foreignId('created_by')->nullable()->default(null)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
