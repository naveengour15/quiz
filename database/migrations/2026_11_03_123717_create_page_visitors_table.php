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
        Schema::create('page_visitors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('page_name', 191)->index();
            $table->string('page_url', 500);
            $table->string('route_name', 191)->nullable()->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('device_type', 30)->nullable(); // Mobile, Tablet, Desktop
            $table->string('browser', 50)->nullable();     // Chrome, Safari, etc.
            $table->string('platform', 50)->nullable();    // Android, iOS, Windows, etc.
            $table->text('user_agent')->nullable();
            $table->date('visit_date')->index();
            $table->timestamps();

            $table->index(['visit_date', 'page_name']);
            $table->index(['created_at']);
            $table->index(['ip_address', 'visit_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_visitors');
    }
};
