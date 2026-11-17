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
        Schema::table('action_trails', function (Blueprint $table) {
            $table->unsignedBigInteger('quiz_id')->nullable()->after('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable()->after('quiz_id');
            $table->unsignedBigInteger('survey_id')->nullable()->after('user_id');       
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('action_trails', function (Blueprint $table) {
            $table->dropColumn([
                'quiz_id',
                'user_id',
                'survey_id',
            ]);
        });
    }
};
