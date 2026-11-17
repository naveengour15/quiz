<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redeem_request_off_day', function (Blueprint $table) {
            $table->id();

            // Tenant ID
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->index();

            // Year
            $table->year('year')->nullable();

            // Month: 1 to 12
            $table->unsignedTinyInteger('month')->nullable();

            // Dates stored as JSON array: [5, 10, 15, 25]
            $table->json('date')->nullable();

            // Status: active / inactive, default NULL
            $table->enum('status', ['active', 'inactive'])
                ->nullable()
                ->default(null);

            $table->timestamps();

            // One record per tenant, year and month
            $table->unique(
                ['tenant_id', 'year', 'month'],
                'redeem_off_day_tenant_year_month_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redeem_request_off_day');
    }
};
