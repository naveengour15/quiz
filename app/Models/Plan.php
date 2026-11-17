<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'tier_level', 'amount', 'days', 'extra_days', 
        'max_devices', 'features', 'status',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }
}
