<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempQuizUser extends Model
{
    use SoftDeletes;

    protected $table = 'temp_quiz_users';

    protected $fillable = [
        'tenant_id',
        'quiz_type',
        'quiz_id',
        'user_id',
        'status',
        'fifty_fifty',
        'switch_option',
        'question_id',
        'out_status',
        'remark',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'quiz_id' => 'integer',
        'user_id' => 'integer',
        'question_id' => 'integer',
        'fifty_fifty' => 'boolean',
        'switch_option' => 'boolean',
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
        return $this->belongsTo(TempQuizQuestion::class, 'question_id');
    }

    public function tempQuizQuestionUserAnswers()
    {
        return $this->hasMany(TempQuizQuestionUserAnswer::class, 'user_id', 'user_id');
    }
}