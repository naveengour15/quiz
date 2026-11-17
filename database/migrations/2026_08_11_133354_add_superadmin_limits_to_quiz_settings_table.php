<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->integer('superadmin_que_limit')->default(10)->after('user_que_limit');
            $table->integer('superadmin_quiz_limit')->default(10)->after('superadmin_que_limit');
            $table->integer('superadmin_referral_limit')->default(10)->after('superadmin_quiz_limit');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->dropColumn([
                'superadmin_que_limit',
                'superadmin_quiz_limit',
                'superadmin_referral_limit',
            ]);
        });
    }
};