<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempQuizWinner extends Model
{
    use SoftDeletes;

    protected $table = 'temp_quiz_winners';

    protected $fillable = [
        'tenant_id',
        'quiz_id',
        'user_id',
        'total_question',
        'total_correct_answer',
        'total_time',
        'per_ans_average_time',
        'rank',
        'level',
        'reward_type',
        'reward_id',
        'reward',
        'voucher_history_id',
        'reward_detail',
        'status',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'quiz_id' => 'integer',
        'user_id' => 'integer',
        'total_question' => 'integer',
        'total_correct_answer' => 'integer',
        'total_time' => 'float',
        'per_ans_average_time' => 'float',
        'rank' => 'integer',
        'reward_id' => 'integer',
        'voucher_history_id' => 'integer',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rankReward()
    {
        return $this->belongsTo(QuizRankReward::class, 'reward_id');
    }

    public function voucherHistory()
    {
        return $this->belongsTo(VoucherHistory::class);
    }
}