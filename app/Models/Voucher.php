<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vouchers';

    protected $fillable = [
        'tenant_id',
        'name',
        'category',
        'partner',
        'voucher_point',
        'stock',
        'description',
        'expire_duration',
        'status',
        'photo',
    ];

    protected $casts = [
        'coin_cost' => 'integer',
        'cash_value' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function histories()
    {
        return $this->hasMany(VoucherHistory::class, 'voucher_id', 'id');
    }

    // Agar sirf pending count chahiye toh ye bhi add kar sakte hain
    public function pendingHistories()
    {
        return $this->hasMany(VoucherHistory::class, 'voucher_id', 'id')
                    ->where('status', 'pending');
    }
}