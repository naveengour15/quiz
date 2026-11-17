<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_analytics', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
        
            $table->foreignId('ad_id')
                ->constrained('ads')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('unique_views')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('favorites')->default(0);
            $table->unsignedBigInteger('shares')->default(0);
            $table->unsignedBigInteger('conversions')->default(0);

            $table->decimal('ctr', 8, 2)->default(0);
            $table->decimal('spent_amount', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_analytics');
    }
};