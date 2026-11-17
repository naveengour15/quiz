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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();

            $table->string('name');
            $table->string('category');
            $table->string('partner')->nullable();

            $table->unsignedInteger('voucher_point')->default(0);

            $table->unsignedInteger('stock')->nullable();
            
            $table->text('description')->nullable();
            $table->integer('expire_duration')->nullable()->comment('in days');

            $table->enum('status', [
                'active',
                'inactive',
                'out_of_stock'
            ])->default('active');

            $table->string('photo')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};