<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PracticeQuiz extends Model
{
    use SoftDeletes;

    protected $table = 'practice_quizzes';

    protected $fillable = [
        'quiz_id', 'tenant_id', 'title', 'category_id', 'sub_category_id', 'level_id', 'type', 'visibility', 'password',
        'retake_allowed', 'max_attempts', 'pass_percentage', 'certificate_on_pass', 'no_of_points', 
        'availability_start', 'availability_end', 'question_limit', 'no_of_players', 'total_reward_amount', 
        'negative_marking', 'approve_status', 'notes', 'creator_id', 'publisher_id', 'scheduler_id', 'archivor_id', 
        'status', 'complete_status', 'scheduled_at', 'per_que_time_limit_sec', 'login_time_period', 
        'before_minute_entry', 'time_limit_minutes', 'fifty_fifty', 'switch_option',
    ];

    protected $casts = [
        'retake_allowed' => 'boolean', 'certificate_on_pass' => 'boolean', 'negative_marking' => 'boolean',
        'fifty_fifty' => 'boolean', 'switch_option' => 'boolean', 'pass_percentage' => 'decimal:2',
        'no_of_points' => 'decimal:2', 'total_reward_amount' => 'decimal:2',
        'availability_start' => 'datetime', 'availability_end' => 'datetime', 'scheduled_at' => 'datetime', 'login_time_period' => 'datetime',
    ];

    // ✅ NEW RELATIONSHIP: Practice Quiz ke questions ke liye
    public function practiceQuizQuestions()
    {
        return $this->hasMany(PracticeQuizQuestion::class, 'practice_quiz_id');
    }

    public function category() { return $this->belongsTo(Category::class); }
    public function level() { return $this->belongsTo(Level::class, 'level_id'); }
    public function quizType() { return $this->belongsTo(QuizType::class, 'type'); }
    public function tenant() { return $this->belongsTo(Tenant::class, 'tenant_id'); }
    public function rankRewards() { return $this->hasMany(QuizRankReward::class, 'quiz_id', 'quiz_id'); }
    
 

    public function quizUsers()
    {
        return $this->hasMany(QuizUser::class, 'quiz_id');
    }

    public function practiceQuizUsers()
    {
        return $this->hasMany(PracticeQuizUser::class, 'quiz_id');
    }

    public function userJoin()
    {
        return $this->hasOne(QuizUser::class, 'quiz_id');
    }

    public function practiceUserJoin()
    {
        return $this->hasOne(PracticeQuizUser::class, 'quiz_id');
    }

    public function quizStatus()
    {
        return $this->hasOne(QuizStatus::class, 'quiz_id');
    }

    public function adViewHistory()
    {
        return $this->hasMany(AdViewHistory::class, 'quiz_id', 'id');
    }

    public function adClickHistory()
    {
        return $this->hasMany(AdClickHistory::class, 'quiz_id', 'id');
    }
}
