<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temp_quiz_rank_rewards', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');            
            $table->enum('quiz_type', ['quiz', 'fff'])->default('quiz');
            $table->unsignedBigInteger('quiz_id');

            $table->unsignedInteger('start_rank')->nullable();
            $table->unsignedInteger('end_rank')->nullable();

            $table->enum('type', [
                'point',
                'coupon',
                'gift',
                'cashback',
                'voucher',
            ])->default('point');

            $table->decimal('reward', 10, 2)->nullable();
            $table->unsignedBigInteger('voucher_id')->nullable();

            $table->longText('voucher_histories_id')
                ->nullable();

            $table->integer('expire_duration')
                ->nullable()
                ->comment('In days');

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('quiz_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temp_quiz_rank_rewards');
    }
};