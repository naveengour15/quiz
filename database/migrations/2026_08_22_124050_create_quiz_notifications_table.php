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
        Schema::create('quiz_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('user_id');
            $table->string('user_name')->nullable();
            $table->string('quiz_name')->nullable();
            $table->dateTime('quiz_schedule_time')->nullable();
            $table->dateTime('notificationactiv_date_time')->nullable();
            $table->enum('status', ['pending', 'Sent','failed'])->default('pending');
            $table->text('msg')->nullable();
            $table->dateTime('sent_date_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_notifications');
    }
};
