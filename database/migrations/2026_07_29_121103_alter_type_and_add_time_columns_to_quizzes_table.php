<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {

            // Change type from enum to nullable unsignedBigInteger
            $table->unsignedBigInteger('type')->nullable()->default(null)->change();

            // After negative_marking
            $table->integer('before_minute_entry')
                ->nullable()
                ->default(null)
                ->after('negative_marking');

            $table->integer('per_que_time_limit_sec')
                ->nullable()
                ->default(null)
                ->after('before_minute_entry');

            $table->timestamp('login_time_period')
                ->nullable()
                ->default(null)
                ->after('per_que_time_limit_sec');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {

            $table->dropColumn([
                'before_minute_entry',
                'per_que_time_limit_sec',
                'login_time_period',
            ]);

            $table->enum('type', [
                'standard',
                'timed',
                'rapid_fire',
                'battle',
                'live',
                'adaptive',
            ])->default('standard')->change();
        });
    }
};