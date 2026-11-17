<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempQuizRankReward extends Model
{
    use SoftDeletes;

    protected $table = 'temp_quiz_rank_rewards';

    protected $fillable = [
        'tenant_id',
        'quiz_type',
        'quiz_id',
        'start_rank',
        'end_rank',
        'type',
        'reward',
        'voucher_id',
        'voucher_histories_id',
        'expire_duration',
        'status',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'quiz_id' => 'integer',
        'start_rank' => 'integer',
        'end_rank' => 'integer',
        'reward' => 'decimal:2',
        'voucher_id' => 'integer',
        'expire_duration' => 'integer',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
}