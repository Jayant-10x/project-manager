<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->enum('category', ['Contract', 'KYC/GST', 'Invoice', 'Proposal', 'Quotation', 'Delivery Proof', 'AMC', 'Other'])->default('Other');
            $table->string('title', 200);
            $table->string('file_name', 200);
            $table->string('stored_as', 255);
            $table->string('mime', 120)->default('application/octet-stream');
            $table->unsignedInteger('size_kb')->default(0);
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('documents');
    }
};
