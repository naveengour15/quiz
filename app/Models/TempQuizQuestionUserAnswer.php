<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempQuizQuestionUserAnswer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'temp_quiz_question_user_answers';

    protected $fillable = [
        'tenant_id',
        'quiz_type',
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
        'tenant_id'   => 'integer',
        'quiz_id'     => 'integer',
        'question_id' => 'integer',
        'user_id'     => 'integer',
        'status'      => 'boolean',
        'total_time'  => 'double',
        'ans_time'    => 'datetime:H:i:s.v',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function question()
    {
        return $this->belongsTo(TempQuizQuestion::class, 'question_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
