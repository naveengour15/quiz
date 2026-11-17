<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class RedeemVoucherSetting extends Model
{
    use BelongsToTenant;

    protected $table = 'redeem_voucher_settings';

    protected $fillable = [
        'tenant_id',
        'type',
        'limit_value',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'limit_value' => 'integer',
        ];
    }
}