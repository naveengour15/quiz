<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'quiz_question';

    protected $fillable = [
        'tenant_id',
        'quiz_id',
        'question_id',
        'order',
        'question_text',
        'type',
        'media_url',
        'options',
        'correct_answer',
        'hindi_question',
        'hindi_options',
        'hindi_correct_answer',
        'audio_file',
        'bot_correct_answer',
        'explanation',
        'category_id',
        'sub_category',
        'difficulty',
        'language',
        'time_limit_seconds',
        'start_time',
        'end_time',
        'xp_value',
        'negative_marking',
        'status',
        'tags',
        'source',
        'moderation_status',
        'start_time',
        'end_time',
        'is_switch_question',
        'question_audio',
    ];

    protected $casts = [
        'options' => 'array',
        'hindi_options' => 'array',
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

    public function getMediaUrlAttribute($value)
    {
        $media = $value ?: ($this->attributes['media'] ?? null);
        if (empty($media) && $this->relationLoaded('question') && $this->question) {
            $media = $this->question->media_url ?? $this->question->media ?? $this->question->image_file ?? null;
        }

        if (empty($media)) {
            return null;
        }

        $media = trim((string) $media);
        if (str_starts_with($media, 'http://') || str_starts_with($media, 'https://')) {
            return $media;
        }

        $cleanPath = preg_replace('/^https?:\/\/[^\/]+\//i', '', $media);
        $cleanPath = ltrim($cleanPath, '/');

        $awsUrl = env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com');
        if (!empty($awsUrl)) {
            return rtrim($awsUrl, '/') . '/' . $cleanPath;
        }

        return asset($media);
    }
}