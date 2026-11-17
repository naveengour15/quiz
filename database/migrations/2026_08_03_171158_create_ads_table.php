<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
        

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('campaign_name');
            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('ad_type', ['video', 'image']);
            $table->string('media');

            $table->string('cta')->nullable();
            $table->string('destination_url')->nullable();

            $table->enum('budget_type', ['daily', 'total']);
            $table->decimal('budget', 12, 2);

            $table->date('start_date');
            $table->date('end_date');

            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();

            $table->tinyInteger('age_from')->nullable();
            $table->tinyInteger('age_to')->nullable();

            $table->enum('gender', ['all', 'male', 'female'])->default('all');

            $table->string('language')->nullable();
            $table->text('interests')->nullable();

            $table->enum('status', [
                'draft',
                'pending',
                'active',
                'paused',
                'completed',
                'rejected'
            ])->default('draft');

            $table->boolean('is_featured')->default(false);
            $table->integer('priority')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};