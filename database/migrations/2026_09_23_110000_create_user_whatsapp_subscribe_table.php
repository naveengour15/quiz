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
        Schema::create('user_whatsapp_subscribe', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['subscribed', 'unsubscribed'])->default('subscribed');
            $table->timestamps();

            // Unique per tenant + user
            $table->unique(['tenant_id', 'user_id'], 'user_wa_sub_tenant_user_unique');

            // Individual indexes for performance
            $table->index('tenant_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_whatsapp_subscribe');
    }
};
