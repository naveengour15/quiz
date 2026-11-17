<?php

// app/Models/PracticeQuizQuestion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PracticeQuizQuestion extends Model
{
    use SoftDeletes;

    protected $table = 'practice_quiz_question';

    protected $fillable = [
        'tenant_id', 'practice_quiz_id', 'question_id', 'order', 'is_switch_question', 
        'question_text', 'question_audio', 'type', 'media_url', 'options', 'correct_answer', 
        'hindi_question', 'hindi_options', 'hindi_correct_answer', 'audio_file', 'bot_correct_answer', 
        'explanation', 'category_id', 'sub_category', 'difficulty', 'language', 'time_limit_seconds', 
        'start_time', 'end_time', 'xp_value', 'negative_marking', 'status', 'tags', 'source', 'moderation_status',
    ];

    protected $casts = [
        'options' => 'array', 'hindi_options' => 'array', 'is_switch_question' => 'boolean',
    ];

    // ✅ FIXED RELATIONSHIP: PracticeQuiz se link karein, Quiz se nahi
    public function practiceQuiz()
    {
        return $this->belongsTo(PracticeQuiz::class, 'practice_quiz_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }


      
}