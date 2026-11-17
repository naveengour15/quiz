<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users_whatsapp_notifies', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');

            $table->enum('status', ['active', 'inactive'])
                ->default('inactive');

            $table->timestamps();

            // Unique per tenant + user
            $table->unique(
                ['tenant_id', 'user_id'],
                'users_whatsapp_notifies_tenant_user_unique'
            );

            // Optional individual indexes
            $table->index('tenant_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_whatsapp_notifications');
    }
};