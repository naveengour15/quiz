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
        Schema::table('quiz_users', function (Blueprint $table) {
            $table->boolean('fifty_fifty')->default(false)->after('status');
            $table->boolean('switch_option')->default(false)->after('fifty_fifty');
            $table->unsignedBigInteger('question_id')->nullable()->after('switch_option');
       
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_users', function (Blueprint $table) {
           $table->dropColumn([
                'fifty_fifty',
                'switch_option',
                'question_id'
            ]);
        });
    }
};
