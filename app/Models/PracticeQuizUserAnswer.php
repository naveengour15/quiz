<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PracticeQuizUserAnswer extends Model
{
    use HasFactory;

    protected $table = 'practice_quiz_user_answers';

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
        'status' => 'integer',
        'total_time' => 'integer',
        'show_time' => 'datetime',
        'ans_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function question()
    {
        return $this->belongsTo(QuizQuestion::class, 'question_id');
    }
}
