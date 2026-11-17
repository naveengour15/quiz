<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('apk_updates', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->default(1);
            $table->string('version'); // e.g., "1.0.2"
            $table->integer('build_number'); // e.g., 2 (Flutter build number)
            $table->text('release_notes')->nullable(); // "New features added"
            $table->string('apk_url')->default('/storage/app-release.apk'); // Fixed URL
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('apk_updates');
    }
};