<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processing_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->decimal('recharge', 8, 2)->default(0)->comment('In percentage (%)');
            $table->decimal('electricity_bill', 8, 2)->default(0)->comment('In percentage (%)');
            $table->decimal('voucher', 8, 2)->default(0)->comment('In percentage (%)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processing_fees');
    }
};
