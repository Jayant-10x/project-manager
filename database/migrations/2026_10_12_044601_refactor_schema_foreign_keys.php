<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['onboarded_by', 'created_by']);
            $table->foreignId('sales_person_id')->nullable()->constrained('sales_persons')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['coordinator', 'created_by']);
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('entered_by');
            $table->foreignId('entered_by_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('uploaded_by');
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('followups', function (Blueprint $table) {
            $table->dropColumn('created_by');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['assigned_to', 'created_by']);
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // Clear orphan notification records before altering
        DB::table('notifications')->truncate();

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('user_email');
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
        });

        Schema::table('audit_log', function (Blueprint $table) {
            $table->dropColumn('who');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::table('audit_log', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            $table->string('who', 80)->nullable();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            $table->string('user_email', 160)->nullable()->index();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['assigned_to_id']);
            $table->dropForeign(['created_by_id']);
            $table->dropColumn(['assigned_to_id', 'created_by_id']);

            $table->string('assigned_to', 80)->nullable();
            $table->string('created_by', 80)->nullable();
        });

        Schema::table('followups', function (Blueprint $table) {
            $table->dropForeign(['created_by_id']);
            $table->dropColumn('created_by_id');

            $table->string('created_by', 80)->nullable();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by_id']);
            $table->dropColumn('uploaded_by_id');

            $table->string('uploaded_by', 80)->nullable();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['entered_by_id']);
            $table->dropColumn('entered_by_id');

            $table->string('entered_by', 80)->nullable();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['coordinator_id']);
            $table->dropForeign(['created_by_id']);
            $table->dropColumn(['coordinator_id', 'created_by_id']);

            $table->string('coordinator', 80)->nullable();
            $table->string('created_by', 80)->nullable();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['sales_person_id']);
            $table->dropForeign(['created_by_id']);
            $table->dropColumn(['sales_person_id', 'created_by_id']);

            $table->string('onboarded_by', 80)->nullable()->index();
            $table->string('created_by', 80)->nullable();
        });
    }
};
