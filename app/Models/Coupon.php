<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'code', 'discount_type', 'discount_value', 'min_purchase',
        'usage_limit_total', 'usage_limit_per_user', 'used_count',
        'applicable_plans', 'expiry_date', 'status',
    ];

    protected $casts = [
        'applicable_plans' => 'array',
        'expiry_date' => 'date',
    ];
}
