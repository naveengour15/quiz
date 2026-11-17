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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');

            $table->unsignedBigInteger('bdm_id')->nullable();

            $table->string('name');

            $table->string('slug')->nullable()->index();

            $table->string('domain')->nullable()->index();

            $table->string('sub_domain')->nullable()->index();

            $table->string('prefix', 10)->nullable();

            $table->string('email');

            $table->string('phone')->nullable();

            $table->string('logo')->nullable();

            $table->string('pan_number', 100)->nullable();

            $table->string('gstin')->nullable();

            $table->boolean('is_gst')->default(false);

            $table->string('address')->nullable();

            $table->decimal('latitude', 10, 8)->nullable();

            $table->decimal('longitude', 11, 8)->nullable();

            $table->string('address1')->nullable();

            $table->string('landmark')->nullable();

            $table->string('pincode')->nullable();

            $table->string('city')->nullable();

            $table->string('state')->nullable();

            $table->string('website')->nullable();

            $table->string('qr_code')->nullable();

            $table->enum('status', [
                'active',
                'inactive'
            ])->default('active');

            $table->string('system_type')->nullable();

            $table->enum('online_status', [
                'online',
                'offline'
            ])->default('offline');

            $table->enum('payment_option', [
                'active',
                'inactive'
            ])->default('inactive');

            $table->enum('store_status', [
                'active',
                'inactive'
            ])->default('inactive');

            $table->longText('css_theme')->nullable();

            $table->string('theme', 100)->default('webapp');

            $table->string('qr_theme')->nullable();

            $table->string('menu_theme')->nullable();

            $table->string('website_color_theme')->nullable();

            $table->string('favicon')->nullable();

            $table->string('dark_logo')->nullable();

            $table->string('seo_title')->nullable();

            $table->text('seo_description')->nullable();

            $table->text('seo_keywords')->nullable();

            $table->time('open_time')->nullable();

            $table->time('close_time')->nullable();

            $table->enum('off_day', [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday'
            ])->nullable();

            $table->integer('updated_by_id')->nullable();

            $table->string('updated_by_name')->nullable();

            $table->string('updated_by_role')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
