<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('code', 50);

            $table->enum('referral_point_provide', ['yes', 'no'])
                ->default('no');

            $table->unsignedBigInteger('referral_user_id')->nullable();

            $table->enum('used', ['pending', 'used'])
                ->default('pending');

            $table->timestamp('used_date')->nullable();

            $table->enum('status', ['active', 'inactive'])
                ->default('active');

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('user_id');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_codes');
    }
};