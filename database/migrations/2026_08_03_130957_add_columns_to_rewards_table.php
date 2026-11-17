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
        Schema::table('rewards', function (Blueprint $table) {
            $table->text('description')->nullable()->after('stock');
            $table->string('photo')->nullable()->after('description');
            $table->integer('no_of_coupon')->default(0)->after('photo');
            $table->integer('points')->default(0)->after('no_of_coupon');
            $table->integer('expire_duration')->nullable()->comment('In days')->after('points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'photo',
                'no_of_coupon',
                'points',
                'expire_duration',
            ]);
        });
    }
};