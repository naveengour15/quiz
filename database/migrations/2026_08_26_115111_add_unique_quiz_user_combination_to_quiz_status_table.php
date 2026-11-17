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
        Schema::table('quiz_status', function (Blueprint $table) {
            $table->unique(
                ['tenant_id', 'quiz_id'],
                'quiz_status_tenant_quiz_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_status', function (Blueprint $table) {
            $table->dropUnique('quiz_status_tenant_quiz_unique');
        });
    }
};
