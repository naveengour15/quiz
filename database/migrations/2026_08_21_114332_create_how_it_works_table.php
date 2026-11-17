<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('how_it_works', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->longText('description')->nullable();
            $table->timestamps();
        });

        // ✅ Bilkul Simple 2-Line Default Entry
        DB::table('how_it_works')->insert([
            [
                'title' => 'How to Play',
                'description' => 'Join a quiz, answer questions within the time limit, and win exciting rewards. Fair play is mandatory for all participants.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('how_it_works');
    }
};