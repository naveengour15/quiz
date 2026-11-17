<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('quiz_users')) {
            Schema::table('quiz_users', function (Blueprint $table) {
                if (!Schema::hasColumn('quiz_users', 'out_status')) {
                    $table->enum('out_status', ['waiting', 'in', 'out'])->default('waiting')->after('question_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('quiz_users')) {
            Schema::table('quiz_users', function (Blueprint $table) {
                if (Schema::hasColumn('quiz_users', 'out_status')) {
                    $table->dropColumn('out_status');
                }
            });
        }
    }
};
