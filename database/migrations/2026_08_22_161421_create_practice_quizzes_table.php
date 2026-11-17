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
        Schema::create('practice_quizzes', function (Blueprint $table) {

            $table->id();

            // Original Quiz ID
            $table->unsignedBigInteger('quiz_id')->nullable();

            $table->unsignedBigInteger('tenant_id')->nullable();

            $table->string('title')->nullable();

            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('sub_category_id')->nullable();
            $table->unsignedBigInteger('level_id')->nullable();
            $table->unsignedBigInteger('type')->nullable();

            $table->string('visibility')->nullable();
            $table->string('password')->nullable();

            $table->boolean('retake_allowed')->default(false);
            $table->integer('max_attempts')->nullable();

            $table->decimal('pass_percentage', 5, 2)->nullable();

            $table->boolean('certificate_on_pass')->default(false);

            $table->decimal('no_of_points', 10, 2)->nullable();

            $table->datetime('availability_start')->nullable();
            $table->datetime('availability_end')->nullable();

            $table->integer('question_limit')->nullable();
            $table->integer('no_of_players')->nullable();

            $table->decimal('total_reward_amount', 10, 2)->nullable();

            $table->boolean('negative_marking')->default(false);

            $table->enum('approve_status', [
                'pending',
                'approved',
                'rejected',
                'cancelled'
            ])->default('approved');

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('publisher_id')->nullable();
            $table->unsignedBigInteger('scheduler_id')->nullable();
            $table->unsignedBigInteger('archivor_id')->nullable();

            $table->enum('status', ['draft',
                'scheduled',
                'published',
                'archived'
            ])->default('published');
            $table->enum('complete_status', ['pending','completed'])->default('pending');

            $table->datetime('scheduled_at')->nullable();

            $table->integer('per_que_time_limit_sec')->nullable();

            $table->datetime('login_time_period')->nullable();

            $table->integer('before_minute_entry')->nullable();

            $table->integer('time_limit_minutes')->nullable();

            $table->boolean('fifty_fifty')->default(false);

            $table->boolean('switch_option')->default(false);

            $table->timestamps();

            $table->softDeletes();

          
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_quizzes');
    }
};