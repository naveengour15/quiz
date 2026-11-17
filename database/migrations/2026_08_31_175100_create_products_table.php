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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
             // Tenant
            $table->unsignedBigInteger('tenant_id')->nullable();
            // Basic Details
            $table->string('name');
            $table->string('slug');
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();

            // Product Data
            $table->json('attributes')->nullable();
            $table->longText('photos')->nullable();
            $table->string('modelno')->nullable();
            $table->string('sku')->nullable();
            $table->string('isbn_upc_barcode')->nullable();
            // Pricing
            $table->decimal('sale_price', 9, 2)->default('0.00');
            $table->decimal('compare_price', 9, 2)->default('0.00');
            $table->decimal('buy_price', 9, 2)->default('0.00');
            // Size / Stock
            $table->string('item_size')->nullable();
            $table->integer('available_stock')->default(1);
            $table->tinyInteger('subtrack_stock')->default(1);

            $table->enum('stock_status', [
                'in_stock',
                'out_of_stock',
                'pre_order',
                '2-3days'
            ])->nullable();

            $table->integer('min_item_order')->nullable();
            $table->integer('maximum_item_order')->nullable();
            $table->date('stock_avaliable_date')->nullable();

            // Variant
            $table->tinyInteger('is_variant')->default(1);
            $table->longText('variant')->nullable();

            // Specifications / Tags
            $table->json('specifications')->nullable();
            $table->text('tags')->nullable();

            // Shipping
            $table->decimal('shipping_charge', 9, 2)->default(0.00);

            // Dimensions
            $table->string('length')->nullable();
            $table->string('breadth')->nullable();
            $table->string('height')->nullable();
            $table->string('weight')->nullable();

            // Rating
            $table->decimal('avg_rating', 2, 1)->default(0.0);
            $table->integer('count_rating')->default(0);

            // Status
            $table->enum('status', [
                'active',
                'inactive'
            ])->default('active');

            // Category / Parent / Brand
            $table->unsignedBigInteger('cat_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('slug');
            $table->index('status');

            $table->index([
                'tenant_id',
                'cat_id',
                'parent_id',
                'status'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
