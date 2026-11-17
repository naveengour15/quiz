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
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
           
            $table->string('type')->nullable();
            $table->text('name')->nullable();
            
            $table->enum('reward_type', ['point', 'quiz'])->default('quiz');
                 
            $table->integer('point')->nullable();
            $table->integer('quiz')->nullable();
            
            $table->enum('published', ['published', 'scheduled'])->default('published');
            $table->datetime('published_at')->nullable();
            
            $table->enum('status', ['active', 'inactive'])
                  ->default('active');

            $table->softDeletes();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};