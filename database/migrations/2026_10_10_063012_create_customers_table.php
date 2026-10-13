<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('company', 160)->unique();
            $table->string('contact_name', 80)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('alt_phone', 30)->nullable();
            $table->string('city', 60)->nullable();
            $table->string('state', 60)->nullable();
            $table->text('address')->nullable();
            $table->string('gstin', 20)->nullable();
            $table->string('pan', 15)->nullable();
            $table->string('billing_name', 160)->nullable();
            $table->text('billing_addr')->nullable();
            $table->string('source', 60)->nullable();
            $table->string('industry', 60)->nullable();
            $table->foreignId('sales_person_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['Active', 'On Hold', 'Completed', 'Churned'])->default('Active');
            $table->text('internal_notes')->nullable();
            $table->string('created_by', 80)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('customers');
    }
};
