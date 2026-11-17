<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apk_updates', function (Blueprint $table) {
            $table->string('release_title')
                  ->nullable()
                  ->after('build_number');
        });
    }

    public function down(): void
    {
        Schema::table('apk_updates', function (Blueprint $table) {
            $table->dropColumn('release_title');
        });
    }
};