<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_gift_vouchers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->default(1);

            $table->string('name');
            $table->string('type')->nullable();
            $table->text('description')->nullable();

            $table->decimal('amount', 12, 2)->default(0);

            $table->unsignedInteger('no_of_voucher')->default(0);
            $table->string('image')->nullable();
            $table->unsignedInteger('no_of_used')->default(0);

            $table->enum('status', ['active', 'inactive'])
                ->default('active');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_gift_vouchers');
    }
};