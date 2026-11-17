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
        Schema::create('web_enquiries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('phone')->required();
            $table->string('otp')->nullable();
            $table->string('otp_verify')->nullable();

            $table->integer('current_step')->default(0);

            $table->string('business_name')->nullable();
            $table->string('email')->nullable();
            $table->string('business_type')->nullable();

            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();

            $table->enum('inquiry_status', ['pending','inprogress', 'store_created', 'review_completed'])->default('inprogress');

            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('updated_by_name')->nullable();
            $table->unsignedBigInteger('updated_by_role_id')->nullable();
            $table->timestamps();
              $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_enquiries');
    }
};
