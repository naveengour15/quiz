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
            $table->decimal('total_time', 10, 3)->default(0)->change();
            $table->decimal('per_ans_average_time', 10, 3)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_winners', function (Blueprint $table) {
            $table->integer('total_time')->default(0)->change();
            $table->integer('per_ans_average_time')->default(0)->change();
        });
    }
};
