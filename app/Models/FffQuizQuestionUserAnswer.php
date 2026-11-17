<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FffQuizQuestionUserAnswer extends Model
{
    use SoftDeletes;

    protected $table = 'fff_quiz_question_user_answers';

    protected $fillable = [
        'tenant_id',
        'quiz_id',
        'question_id',
        'user_id',
        'answer',
        'status',
        'show_time',
        'ans_time',
        'total_time',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'quiz_id' => 'integer',
        'question_id' => 'integer',
        'user_id' => 'integer',
        'status' => 'boolean',
        'show_time' => 'datetime:H:i:s',
        'ans_time' => 'datetime:H:i:s.v',
        'total_time' => 'float',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function question()
    {
        return $this->belongsTo(QuizQuestion::class, 'question_id');
    }
}