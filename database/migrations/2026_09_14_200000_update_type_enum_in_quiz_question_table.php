<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $types = "'mcq','true_false','image','audio','video','puzzle','fastest_finger_first','image_audio'";

        if (Schema::hasTable('quiz_question')) {
            DB::statement("
                ALTER TABLE quiz_question
                MODIFY COLUMN type ENUM($types)
                NOT NULL DEFAULT 'mcq'
            ");
        }
    }

    public function down(): void
    {
        $types = "'mcq','true_false','image','audio','video','puzzle'";

        if (Schema::hasTable('quiz_question')) {
            DB::statement("
                ALTER TABLE quiz_question
                MODIFY COLUMN type ENUM($types)
                NOT NULL DEFAULT 'mcq'
            ");
        }
    }
};
