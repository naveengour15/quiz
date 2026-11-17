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
        Schema::create('ad_media', function (Blueprint $table) {
            $table->id();
          

            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('ad_id');

            $table->string('banner')->nullable();
            $table->string('large_banner')->nullable();
            $table->string('medium_banner')->nullable();
            $table->string('full_screen')->nullable();

            $table->string('video_portrait')->nullable();
            $table->string('video_landscape')->nullable();

           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_media');
    }
};
