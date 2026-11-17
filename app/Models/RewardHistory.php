<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RewardHistory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'reward_id',
        'reward_type',
        'reward_point',
        'code',
        'assign_to_user_id',
        'assign_date',
        'expire_date',
        'use_date',
        'used_user_id',
        'deduct_point',
        'status',
    ];

    protected $casts = [
        'assign_date' => 'datetime',
        'expire_date' => 'datetime',
        'use_date' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reward()
    {
        return $this->belongsTo(SurveyReward::class, 'reward_id');
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