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
             $table->integer('total_intrested_user')->default(0)->after('total_complete_quiz_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_status', function (Blueprint $table) {
            $table->dropColumn('total_intrested_user');
        });
    }
};
