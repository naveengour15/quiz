<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VoucherHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'voucher_histories';

    protected $fillable = [
        'tenant_id',
        'voucher_id',
        'voucher_type',
        'voucher_point',
        'code',
        'assign_to_user_id',
        'assign_date',
        'expire_duration',
        'expire_date',
        'used_date',
        'used_user_id',
        'status',
    ];

    protected $casts = [
        'assign_date' => 'datetime',
        'expire_date' => 'datetime',
        'used_date' => 'datetime',
        'coin_cost'   => 'integer',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function getvoucher()
    {
         return $this->belongsTo(Voucher::class, 'voucher_id', 'id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assign_to_user_id');
    }

    public function usedUser()
    {
        return $this->belongsTo(User::class, 'used_user_id');
    }
    

    
}
