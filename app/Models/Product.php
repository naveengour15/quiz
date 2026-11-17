<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'summary',
        'description',
        'attributes',
        'photos',
        'modelno',
        'sku',
        'isbn_upc_barcode',
        'sale_price',
        'compare_price',
        'buy_price',
        'item_size',
        'available_stock',
        'subtrack_stock',
        'stock_status',
        'min_item_order',
        'maximum_item_order',
        'stock_avaliable_date',
        'is_variant',
        'variant',
        'specifications',
        'tags',
        'shipping_charge',
        'length',
        'breadth',
        'height',
        'weight',
        'avg_rating',
        'count_rating',
        'status',
        'cat_id',
        'parent_id',
        'brand_id',
    ];

    protected $casts = [
        'photos' => 'array',
        'attributes' => 'array',
        'specifications' => 'array',

        'sale_price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'buy_price' => 'decimal:2',
        'shipping_charge' => 'decimal:2',
        'avg_rating' => 'decimal:1',

        'stock_avaliable_date' => 'date',
        'is_variant' => 'boolean',
        'subtrack_stock' => 'boolean',
    ];

       public function category()
    {
        return $this->belongsTo(
            ProductCategory::class,
            'cat_id'
        );
    }


    public function subcategory()
    {
        return $this->belongsTo(
            ProductCategory::class,
            'parent_id'
        );
    }

 

    public function brand()
    {
        return $this->belongsTo(
            ProductBrand::class,
            'brand_id'
        );
    }
}