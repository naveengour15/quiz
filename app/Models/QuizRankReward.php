<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizRankReward extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'quiz_id',
        'rank_range',
        'start_rank',
        'end_rank',
        'type',
        'reward',
        'voucher_id',
        'voucher_histories_id',
        'coupon_id',
        'expire_duration',
        'status',
    ];

    protected $casts = [
        'voucher_histories_id' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
    
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

     public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    // Relation with VoucherHistories
    public function voucherHistories()
    {
        return $this->hasMany(VoucherHistory::class, 'id', 'voucher_histories_id');
    }
}