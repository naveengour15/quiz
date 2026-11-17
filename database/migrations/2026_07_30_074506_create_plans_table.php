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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');

            $table->string('name');
            $table->unsignedTinyInteger('tier_level')->default(1);

            $table->decimal('amount', 10, 2)->default(0);

            $table->integer('days')->default(0);
            $table->integer('extra_days')->default(0);

            $table->unsignedInteger('max_devices')->default(1);

            $table->json('features')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');

            // Optional Foreign Key
            // $table->foreign('tenant_id')
            //     ->references('id')
            //     ->on('tenants')
            //     ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
