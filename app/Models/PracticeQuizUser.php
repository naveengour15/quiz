<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PracticeQuizUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'practice_quiz_users';

    protected $fillable = [
        'tenant_id',
        'quiz_id',
        'user_id',
        'status',
        'fifty_fifty',
        'switch_option',
        'question_id',
        'total_question',
        'total_correct_answer',
        'total_time',
        'per_ans_average_time',
        'remark',
    ];

    protected $casts = [
        'fifty_fifty' => 'boolean',
        'switch_option' => 'boolean',
        'total_question' => 'integer',
        'total_correct_answer' => 'integer',
        'total_time' => 'integer',
        'per_ans_average_time' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}
