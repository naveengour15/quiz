<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('e_gift_voucher_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('e_gift_voucher_requests', 'fee_percent')) {
                $table->decimal('fee_percent', 8, 2)->default(0)->after('voucher_amount');
            }
            if (!Schema::hasColumn('e_gift_voucher_requests', 'fee_amount')) {
                $table->decimal('fee_amount', 12, 2)->default(0)->after('fee_percent');
            }
            if (!Schema::hasColumn('e_gift_voucher_requests', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->default(0)->after('fee_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('e_gift_voucher_requests', function (Blueprint $table) {
            $table->dropColumn(['fee_percent', 'fee_amount', 'total_amount']);
        });
    }
};
