<?php

namespace App\Models;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;


class RedeemRequestOffDay extends Model
{
    use BelongsToTenant;

    protected $table = 'redeem_request_off_day';

    protected $fillable = [
        'tenant_id',
        'year',
        'month',
        'date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'array',
            'year' => 'integer',
            'month' => 'integer',
        ];
    }
}
