<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temp_quiz_users', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');            
            $table->enum('quiz_type', ['quiz', 'fff'])->default('quiz');
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('user_id');

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->boolean('fifty_fifty')->default(false);
            $table->boolean('switch_option')->default(false);

            $table->unsignedBigInteger('question_id')->nullable();

            $table->enum('out_status', [
                'waiting',
                'in',
                'out',
            ])->default('waiting');

            $table->string('remark', 191)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'quiz_id', 'user_id'],
                'temp_quiz_users_tenant_quiz_user_unique'
            );

            $table->index('tenant_id');
            $table->index('quiz_id');
            $table->index('user_id');

            $table->index(
                ['quiz_id', 'status'],
                'idx_temp_quiz_users_quiz_status'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temp_quiz_users');
    }
};