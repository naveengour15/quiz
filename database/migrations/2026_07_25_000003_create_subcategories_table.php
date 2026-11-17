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
        Schema::create('subcategories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->nullable()->index();

            $table->unsignedBigInteger('category_id');

            $table->string('name');
            $table->string('icon')->nullable();
            $table->text('description')->nullable();

            $table->unsignedInteger('total_question')->default(0);
            $table->unsignedInteger('total_quiz')->default(0);

            $table->unsignedInteger('display_order')->default(1);

            $table->enum('status', ['active', 'inactive'])
                ->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subcategories');
    }
};