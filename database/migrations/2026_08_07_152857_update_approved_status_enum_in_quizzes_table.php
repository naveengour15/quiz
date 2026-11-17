<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE quizzes
            MODIFY COLUMN approve_status
            ENUM('pending', 'approved', 'rejected', 'cancelled')
            NOT NULL DEFAULT 'pending'
        ");

        Schema::table('quizzes', function (Blueprint $table) {
           $table->text('notes')->nullable()->after('approve_status');
            $table->integer('approver_id')->nullable()->after('notes');
            $table->integer('creator_id')->nullable()->after('status');
            $table->integer('publisher_id')->nullable()->after('creator_id');
            $table->integer('scheduler_id')->nullable()->after('publisher_id');
            $table->integer('archivor_id')->nullable()->after('scheduler_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE quizzes
            MODIFY COLUMN approve_status
            ENUM('pending', 'approved', 'rejected')
            NOT NULL DEFAULT 'pending'
        ");
         Schema::table('quizzes', function (Blueprint $table) {
          $table->dropColumn(['notes', 'approver_id', 'creator_id', 'publisher_id', 'scheduler_id', 'archivor_id']);
        });
    }
};