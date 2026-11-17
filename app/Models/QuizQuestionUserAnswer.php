<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizQuestionUserAnswer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'quiz_question_user_answers';

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
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'status'     => 'boolean',
        'total_time' => 'float',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $answer) {
            if ($answer->quiz_id && $answer->user_id) {
                QuizStatus::handleAnswerSubmitted(
                    $answer->quiz_id,
                    $answer->user_id,
                    (bool) $answer->status
                );
            }
        });
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function question()
    {
        return $this->belongsTo(
            QuizQuestion::class,
            'question_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}