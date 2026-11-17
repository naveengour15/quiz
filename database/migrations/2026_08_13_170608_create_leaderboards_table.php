<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('yearly_session');
            $table->unsignedInteger('rank')->nullable();
            $table->decimal('points', 10, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])
            ->default('active');


            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('yearly_session');
            $table->index(['tenant_id', 'yearly_session']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboards');
    }
};