<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FffQuizQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fff_quiz_questions';
    protected $fillable = [
        'tenant_id',
        'quiz_id',
        'question_id',
        'type',
        'language',
        'question_text',
        'question_audio',
        'media_url',
        'options',
        'correct_answer',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'options' => 'array',
        'is_switch_question' => 'boolean',
    ];
    
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function getQuestionAudioAttribute($value)
    {
        $audio = $value ?: ($this->attributes['audio_file'] ?? null);
        if (empty($audio) && $this->relationLoaded('question') && $this->question) {
            $audio = $this->question->question_audio ?? $this->question->audio_file ?? null;
        }

        if (empty($audio)) {
            return null;
        }

        $audio = trim((string) $audio);
        $cleanPath = preg_replace('/^https?:\/\/[^\/]+\//i', '', $audio);
        $cleanPath = ltrim($cleanPath, '/');

        $awsUrl = env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com');
        if (!empty($awsUrl)) {
            return rtrim($awsUrl, '/') . '/' . $cleanPath;
        }

        return asset($audio);
    }

    public function getOptionsAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }
    

}
