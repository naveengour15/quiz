<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('fifty_fifty')->default(false)->after('complete_status');
            $table->boolean('switch_option')->default(false)->after('fifty_fifty');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn([
                'fifty_fifty',
                'switch_option',
            ]);
        });
    }
};