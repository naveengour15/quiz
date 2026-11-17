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
        Schema::table('leader_point_history', function (Blueprint $table) {
             $table->unique(
                ['tenant_id', 'quiz_id', 'user_id'],
                'leader_point_history_tenant_quiz_user_unique'
            );
            $table->float('old_point', 20, 2)->nullable()->after('quiz_id');
            $table->float('new_point', 20, 2)->nullable()->after('point');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leader_point_history', function (Blueprint $table) {
            
            $table->dropUnique('leader_point_history_tenant_quiz_user_unique');
            $table->dropColumn(['old_point', 'new_point']);
        });
    }
};
