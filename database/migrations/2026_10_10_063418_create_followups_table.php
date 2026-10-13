<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->enum('kind', ['Call', 'Email', 'Meeting', 'WhatsApp', 'Note'])->default('Call');
            $table->string('summary', 300);
            $table->text('detail')->nullable();
            $table->timestamp('happened_on')->useCurrent();
            $table->date('next_date')->nullable();
            $table->string('next_note', 200)->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('followups');
    }
};
