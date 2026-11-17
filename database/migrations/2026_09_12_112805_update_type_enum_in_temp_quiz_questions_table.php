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
        Schema::table('temp_quiz_questions', function (Blueprint $table) {
              $table->enum('type', [
                'mcq',
                'true_false',
                'image',
                'audio',
                'video',
                'puzzle',
                'fastest_finger_first',
                'image_audio',
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temp_quiz_questions', function (Blueprint $table) {
            $table->enum('type', [
                'mcq',
                'true_false',
                'image',
                'audio',
                'video',
                'puzzle',
            ])->change();
        });
    }
};
