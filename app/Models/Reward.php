<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'category', 'partner', 'coin_cost', 'cash_value',
        'stock', 'description', 'photo', 'no_of_coupon', 'points', 'expire_duration', 
        'fulfillment_type', 'status', 'image',
    ];

    public function redemptions()
    {
        return $this->hasMany(Redemption::class);
    }
}
