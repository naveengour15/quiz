<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
         * Convert existing invalid JSON values
         * before changing column type.
         */

        // favorite_color
        DB::statement("
            UPDATE users
            SET favorite_color = JSON_ARRAY(favorite_color)
            WHERE favorite_color IS NOT NULL
              AND TRIM(favorite_color) != ''
              AND JSON_VALID(favorite_color) = 0
        ");

        // Empty values
        DB::statement("
            UPDATE users
            SET favorite_color = '[]'
            WHERE favorite_color IS NULL
               OR TRIM(favorite_color) = ''
        ");

        // hobbies
        DB::statement("
            UPDATE users
            SET hobbies = JSON_ARRAY(hobbies)
            WHERE hobbies IS NOT NULL
              AND TRIM(hobbies) != ''
              AND JSON_VALID(hobbies) = 0
        ");

        DB::statement("
            UPDATE users
            SET hobbies = '[]'
            WHERE hobbies IS NULL
               OR TRIM(hobbies) = ''
        ");

        // games
        DB::statement("
            UPDATE users
            SET games = JSON_ARRAY(games)
            WHERE games IS NOT NULL
              AND TRIM(games) != ''
              AND JSON_VALID(games) = 0
        ");

        DB::statement("
            UPDATE users
            SET games = '[]'
            WHERE games IS NULL
               OR TRIM(games) = ''
        ");

        // interest
        DB::statement("
            UPDATE users
            SET interest = JSON_ARRAY(interest)
            WHERE interest IS NOT NULL
              AND TRIM(interest) != ''
              AND JSON_VALID(interest) = 0
        ");

        DB::statement("
            UPDATE users
            SET interest = '[]'
            WHERE interest IS NULL
               OR TRIM(interest) = ''
        ");

        /*
         * Now change columns to JSON.
         */
        Schema::table('users', function (Blueprint $table) {
            $table->json('favorite_color')->nullable()->change();
            $table->json('hobbies')->nullable()->change();
            $table->json('games')->nullable()->change();
            $table->json('interest')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('favorite_color', 191)->nullable()->change();
            $table->text('hobbies')->nullable()->change();
            $table->text('games')->nullable()->change();
            $table->text('interest')->nullable()->change();
        });
    }
};