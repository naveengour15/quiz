<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; 

return new class extends Migration
{
    public function up()
    {
        // 1. Table Create with new name and tenant_id
        Schema::create('redeem_voucher_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->default(1); // Tenant ID default 1
            $table->string('type')->unique(); 
            $table->integer('limit_value')->default(0); 
            $table->enum('status', ['active', 'inactive'])->default('active'); 
            $table->timestamps();
        });

        // 2. Default Values Insert with new table name
        DB::table('redeem_voucher_settings')->insert([
            [
                'tenant_id' => 1,
                'type' => 'recharge_limit_amount',
                'limit_value' => 5000, 
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'tenant_id' => 1,
                'type' => 'recharge_limit_monthly',
                'limit_value' => 1, 
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'tenant_id' => 1,
                'type' => 'voucher_limit_monthly',
                'limit_value' => 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'tenant_id' => 1,
                'type' => 'voucher_limit_amount',
                'limit_value' => 1000, 
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'tenant_id' => 1,
                'type' => 'total_monthly_limit_amount',
                'limit_value' => 1600, 
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function down()
    {
        // 3. Drop table with new name
        Schema::dropIfExists('redeem_voucher_settings');
    }
};