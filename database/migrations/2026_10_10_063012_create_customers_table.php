<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 20)->unique();
            $table->string('company', 160)->unique();
            $table->string('contact_name', 80)->nullable()->default(null);
            $table->string('email', 160)->unique()->nullable()->default(null);
            $table->string('phone', 30)->unique()->nullable()->default(null);
            $table->string('alt_phone', 30)->nullable()->default(null);
            $table->string('city', 60)->nullable()->default(null);
            $table->string('state', 60)->nullable()->default(null);
            $table->text('address')->nullable()->default(null);
            $table->string('gst_number', 20)->unique()->nullable()->default(null);
            $table->string('pan_number', 15)->unique()->nullable()->default(null);
            $table->string('billing_name', 160)->nullable()->default(null);
            $table->text('billing_address')->nullable()->default(null);
            $table->string('source', 60)->nullable()->default(null);
            $table->string('industry', 60)->nullable()->default(null);
            $table->enum('status', ['Active', 'On Hold', 'Completed', 'Churned'])->default('Active');
            $table->text('internal_notes')->nullable()->default(null);
            $table->foreignId('sales_person_id')->nullable()->default(null)->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->default(null)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
