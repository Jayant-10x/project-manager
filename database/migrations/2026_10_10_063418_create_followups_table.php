<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->default(null)->constrained('projects')->nullOnDelete();
            $table->enum('type', ['Call', 'Email', 'Meeting', 'WhatsApp', 'Note'])->default('Call');
            $table->string('summary', 300);
            $table->text('detail')->nullable()->default(null);
            $table->timestamp('happened_on')->useCurrent();
            $table->date('next_date')->nullable()->default(null);
            $table->string('next_note', 200)->nullable()->default(null);
            $table->foreignId('created_by')->nullable()->default(null)->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('followups');
    }
};
