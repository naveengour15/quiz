<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosPlan extends Model
{
     protected $table = 'pos_plans';

    protected $fillable = [
        'tenant_id',
        'plan_name',
        'price',
        'offer_price',
        'validity_days',
        'extra_days',
        'sms',
        'whatsapp',
        'email',
        'view_for',
        'status',
        'create_by',
        'update_by',
    ];

    protected $casts = [
        'price' => 'double',
        'offer_price' => 'double',
        'validity_days' => 'integer',
        'extra_days' => 'integer',
        'sms' => 'integer',
        'whatsapp' => 'integer',
        'email' => 'integer',
    ];
}
