<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_gift_voucher_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('e_gift_voucher_id');

            $table->decimal('wollet_balance', 12, 2)->default(0);
            $table->decimal('voucher_amount', 12, 2)->default(0);

            $table->string('image')->nullable();
            $table->text('url')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            $table->text('remark')->nullable();

            $table->unsignedBigInteger('approver_id')->nullable();
            $table->timestamp('approve_date')->nullable();

            $table->timestamps();

            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index('user_id');
            $table->index('e_gift_voucher_id');
            $table->index('approver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_gift_voucher_requests');
    }
};