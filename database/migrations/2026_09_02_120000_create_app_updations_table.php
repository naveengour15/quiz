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
        Schema::create('app_updations', function (Blueprint $table) {
            $table->id();
            $table->json('user_id')->nullable();
            $table->json('tenant_id')->nullable();
            $table->string('subject', 200);
            $table->longText('message');

            $table->enum('recipient_type', [
                'all_user',
                'all_retailer',
                'individual_user',
                'individual_retailer'
            ]);

            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('sender_name', 191)->nullable();
            $table->enum('status', ['send', 'draft'])->default('send');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_updations');
    }
};
