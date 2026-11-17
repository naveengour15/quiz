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
        Schema::create('notification_history', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('notification_type')->nullable();
            $table->unsignedBigInteger('notification_type_id')->nullable();

            $table->unsignedBigInteger('template_id')->nullable();

            $table->string('type', 50)->default('SMS');

            $table->enum('whatsapp_type', [
                'marketing',
                'utility',
                'authentication'
            ])->default('utility');

            $table->double('average_cost')->nullable();

            $table->string('send_to', 50)->nullable();

            $table->string('title')->nullable();

            $table->text('body')->nullable();

            $table->string('status', 20)->default('failed');

            $table->timestamps();

            $table->index('user_id');
            $table->index(['notification_type', 'notification_type_id'], 'notif_hist_type_type_id_idx');
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_history');
    }
};