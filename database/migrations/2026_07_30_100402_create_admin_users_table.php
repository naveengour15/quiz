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
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->default(1);

            $table->string('name', 191)->nullable();
            $table->string('email', 191)->nullable()->unique();
            $table->string('mobile', 15)->nullable()->unique();

            $table->enum('login_method', ['password', 'otp', 'inactive'])->default('otp');

            $table->string('password', 255);

            $table->string('role', 191)->default('superadmin');
            $table->unsignedSmallInteger('role_id')->default(1);
            $table->string('referral_code')->nullable()->unique();

            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('profile_photo')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('can_login')->default(true);
            $table->boolean('otp_enabled')->default(true);

            $table->string('session_token')->nullable();
            $table->rememberToken();

            $table->timestamp('last_login_at')->nullable();

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('role_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};