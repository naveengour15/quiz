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
     
           // Purana enum column remove
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        // Naya JSON column (multiple select ke liye)
        Schema::table('banners', function (Blueprint $table) {
            $table->json('type')->nullable()->after('url');
        });
     
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
           // JSON column remove
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        // Enum column wapas
        Schema::table('banners', function (Blueprint $table) {
            $table->enum('type', [
                'all',
                'home',
                'before_quiz_start_count',
                'before_answer_count',
                'before_leaderboard_count',
            ])->after('url');
        });
      
    }
};
