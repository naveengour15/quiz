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
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index();

            $table->string('token', 191)->unique();

            $table->string('platform', 16)->default('android');

            $table->string('device_id', 191)->nullable();
            $table->string('brand', 64)->nullable();
            $table->string('manufacturer', 64)->nullable();
            $table->string('model', 128)->nullable();
            $table->string('device_name', 191)->nullable();

            $table->string('os_name', 32)->nullable();
            $table->string('os_version', 32)->nullable();
            $table->unsignedInteger('api_level')->nullable();
            $table->string('security_patch', 32)->nullable();

            $table->string('app_version', 50)->nullable();
            $table->string('app_build', 32)->nullable();
            $table->string('package_name', 191)->nullable();

            $table->string('locale', 32)->nullable();
            $table->string('timezone', 64)->nullable();

            $table->boolean('is_physical_device')->nullable();

            $table->json('extra')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index(
                ['user_id', 'is_active'],
                'device_tokens_user_active_index'
            );

            $table->index(
                ['user_id', 'device_id'],
                'device_tokens_user_device_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};