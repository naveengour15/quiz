<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EGiftVoucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'e_gift_vouchers';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'description',
        'amount',
        'no_of_voucher',
        'image',
        'no_of_used',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'no_of_voucher' => 'integer',
        'no_of_used' => 'integer',
    ];
}