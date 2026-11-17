<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'referred_user_id',
        'referral_code',
        'status',
        'reward_amount',
        'reward_given',
        'reward_at',
        'remarks',
    ];

    protected $casts = [
        'reward_given' => 'boolean',
        'reward_amount' => 'decimal:2',
        'reward_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // User who shared the referral
    public function referrer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // User who registered using the referral
    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
