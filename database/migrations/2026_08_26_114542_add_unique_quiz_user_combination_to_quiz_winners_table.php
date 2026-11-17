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
        Schema::table('quiz_winners', function (Blueprint $table) {
            $table->unique(
                ['tenant_id', 'quiz_id', 'user_id'],
                'quiz_winners_tenant_quiz_user_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_winners', function (Blueprint $table) {
            $table->dropUnique('quiz_winners_tenant_quiz_user_unique');
        });
    }
};
