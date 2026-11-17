<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EGiftVoucherRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'e_gift_voucher_requests';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'e_gift_voucher_id',
        'wollet_balance',
        'voucher_amount',
        'fee_percent',
        'fee_amount',
        'total_amount',
        'image',
        'url',
        'voucher_code',
        'status',
        'remark',
        'approver_id',
        'approve_date',
    ];

    protected $casts = [
        'wollet_balance' => 'decimal:2',
        'voucher_amount' => 'decimal:2',
        'fee_percent' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approve_date' => 'datetime',
    ];

    public function voucher()
    {
        return $this->belongsTo(
            EGiftVoucher::class,
            'e_gift_voucher_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function approver()
    {
        return $this->belongsTo(
            User::class,
            'approver_id'
        );
    }
}