<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redeem_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('request_type')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();

            $table->string('company')->nullable();
            $table->string('mobile', 20)->nullable();

            $table->double('redeem_amount', 15, 2)->default(0);

            $table->string('consumer_number')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'cancelled',
            ])->default('pending');

            $table->text('user_note')->nullable();
            $table->text('approver_note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('zone_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redeem_requests');
    }
};