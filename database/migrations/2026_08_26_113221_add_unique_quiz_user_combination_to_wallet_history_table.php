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
        Schema::table('wallet_history', function (Blueprint $table) {
            $table->unique(
                ['tenant_id', 'quiz_id', 'user_id'],
                'wallet_history_tenant_quiz_user_unique'
            );
            $table->float('old_wallet_balance', 20, 2)->nullable()->after('survey_id');
            $table->float('new_wallet_balance', 20, 2)->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('wallet_history', function (Blueprint $table) {
            $table->dropUnique('wallet_history_tenant_quiz_user_unique');
            $table->dropColumn(['old_wallet_balance', 'new_wallet_balance']);
        });
    }
};
