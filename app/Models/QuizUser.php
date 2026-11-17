<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizUser extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'tenant_id',
        'quiz_id',
        'user_id',
        'status',
        'remark',
        'fifty_fifty',
        'switch_option',
        'question_id',
        'out_status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'fifty_fifty' => 'boolean',
        'switch_option' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::created(function ($quizUser) {
            if ($quizUser->quiz_id) {
                $isEnrolled = ($quizUser->status === 'active' || $quizUser->status === null);
                QuizStatus::recordInterestedAndEnrolledUser(
                    $quizUser->quiz_id,
                    $quizUser->tenant_id ?? 1,
                    $isEnrolled
                );
            }
        });

        static::deleted(function ($quizUser) {
            if ($quizUser->quiz_id) {
                QuizStatus::where('quiz_id', $quizUser->quiz_id)->where('total_enroll_user', '>', 0)->decrement('total_enroll_user');
            }
        });
    }

    /**
     * Tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Quiz
     */
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quizQuestionUserAnswers()
    {
        return $this->hasMany(QuizQuestionUserAnswer::class, 'user_id', 'user_id');
    }

}