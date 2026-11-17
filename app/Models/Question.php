<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'question_text', 'type', 'media_url', 'options', 'correct_answer',
        'hindi_question', 'hindi_options', 'hindi_correct_answer', 'audio_file',
        'explanation', 'category_id', 'sub_category', 'difficulty', 'language',
        'time_limit_seconds', 'xp_value', 'negative_marking', 'status', 'tags',
        'source', 'moderation_status','bot_correct_answer','question_audio',
    ];

    protected $casts = [
        'options' => 'array',
        'hindi_options' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class, 'quiz_question')->withPivot('order');
    }

    public function subCategory()
    {
        return $this->belongsTo(Category::class, 'sub_category');
    }

    public function getMediaUrlAttribute($value)
    {
        $media = $value ?: ($this->attributes['media'] ?? null);
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

    public function getQuestionAudioAttribute($value)
    {
        $audio = $value ?: ($this->attributes['audio_file'] ?? null);
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
}
