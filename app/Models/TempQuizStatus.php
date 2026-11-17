<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempQuizStatus extends Model
{
    use SoftDeletes;

    protected $table = 'temp_quiz_status';

    protected $fillable = [
        'tenant_id',        
        'quiz_type',
        'quiz_id',
        'total_enroll_user',
        'total_played_user',
        'total_complete_quiz_user',
        'total_intrested_user',
        'total_correct_ans',
        'correct_answer_average_time',
        'current_question_id',
        'status',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'quiz_id' => 'integer',
        'total_enroll_user' => 'integer',
        'total_played_user' => 'integer',
        'total_complete_quiz_user' => 'integer',
        'total_intrested_user' => 'integer',
        'total_correct_ans' => 'integer',
        'correct_answer_average_time' => 'integer',
        'current_question_id' => 'integer',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}