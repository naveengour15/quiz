<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'plan_id', 'billing_cycle', 'start_date',
        'renewal_date', 'amount', 'payment_method', 'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'renewal_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
