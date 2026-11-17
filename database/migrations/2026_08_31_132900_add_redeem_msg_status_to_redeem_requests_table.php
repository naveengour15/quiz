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
        Schema::table('redeem_requests', function (Blueprint $table) {
            $table->enum('redeem_msg_status', ['pending', 'Sent', 'failed'])
                ->default('pending')
                ->after('approver_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('redeem_requests', function (Blueprint $table) {
            $table->dropColumn('redeem_msg_status');
        });
    }
};