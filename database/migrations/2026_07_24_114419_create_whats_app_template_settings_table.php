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
        Schema::create('whats_app_template_settings', function (Blueprint $table) {
            $table->id();

            $table->string('template_name');
            $table->string('template_code')->nullable();
            $table->string('language_code')->default('en');
            $table->string('phone_no')->nullable();

            $table->json('data')->nullable();
            $table->text('description')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whats_app_template_settings');
    }
};