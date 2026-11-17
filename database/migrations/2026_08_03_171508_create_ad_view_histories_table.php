<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_view_histories', function (Blueprint $table) {

            $table->id();
             $table->unsignedBigInteger('tenant_id')->nullable();
        
            $table->foreignId('ad_id')
                ->constrained('ads')
                ->cascadeOnDelete();

            $table->foreignId('viewer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('session_id')->nullable();

            $table->ipAddress('ip_address')->nullable();

            $table->string('device')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();

            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();

            $table->string('referrer')->nullable();

            $table->integer('watch_time')->default(0);

            $table->boolean('is_unique')->default(false);

            $table->timestamp('viewed_at')->useCurrent();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_view_histories');
    }
};