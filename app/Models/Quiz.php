<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

class Quiz extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'title', 'category_id', 'sub_category_id', 'level_id', 'type', 'visibility', 'password',
        'retake_allowed', 'max_attempts', 'pass_percentage', 'certificate_on_pass', 'no_of_points', 
        'availability_start', 'availability_end', 'question_limit', 'no_of_players', 'question_limit', 
        'total_reward_amount', 'negative_marking', 'approve_status', 'notes', 'creator_id', 'publisher_id', 
        'scheduler_id', 'archivor_id', 'status', 'complete_status', 'scheduled_at', 'whatsapp_message_sent_before_minutes',
        'per_que_time_limit_sec','login_time_period','before_minute_entry','time_limit_minutes',
        'fifty_fifty','switch_option'
    ];

    protected $casts = [
        'retake_allowed'       => 'boolean',
        'certificate_on_pass'  => 'boolean',
        'negative_marking'     => 'boolean',
        'fifty_fifty'          => 'boolean',
        'switch_option'        => 'boolean',

        'availability_start'   => 'datetime',
        'availability_end'     => 'datetime',
        'scheduled_at'         => 'datetime',
        'login_time_period'    => 'datetime',
        'send_date_time'       => 'datetime',

        'no_of_points'         => 'decimal:2',
        'total_reward_amount'  => 'decimal:2',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::created(function ($quiz) {
            QuizStatus::firstOrCreate(
                ['quiz_id' => $quiz->id],
                [
                    'tenant_id'                   => $quiz->tenant_id ?? 1,
                    'total_enroll_user'           => 0,
                    'total_played_user'           => 0,
                    'total_complete_quiz_user'    => 0,
                    'total_correct_ans'           => 0,
                    'total_intrested_user'        => 0,
                    'correct_answer_average_time' => 0,
                    'status'                      => $quiz->complete_status ?? 'pending',
                ]
            );

            $scheduledAt = $quiz->availability_end ? \Carbon\Carbon::parse($quiz->availability_end)->addMinutes(2) : now()->addMinutes(2);
            QuizCronJobHistory::firstOrCreate(
                [
                    'quiz_id' => $quiz->id,
                    'cron_type' => 'quiz_winner_leaderboard',
                ],
                [
                    'tenant_id' => $quiz->tenant_id ?? 1,
                    'scheduled_at' => $scheduledAt,
                    'status' => 'pending',
                ]
            );
        });

        static::updated(function ($quiz) {
            if ($quiz->isDirty('complete_status')) {
                \Illuminate\Support\Facades\Cache::forget('is_any_quiz_playing_now');
                $statusVal = $quiz->complete_status;
                QuizStatus::where('quiz_id', $quiz->id)->update([
                    'status' => $statusVal,
                ]);

                if ($quiz->complete_status === 'processing') {
                    $scheduledAt = $quiz->availability_end ? \Carbon\Carbon::parse($quiz->availability_end)->addMinutes(2) : now()->addMinutes(2);
                    QuizCronJobHistory::firstOrCreate(
                        [
                            'quiz_id' => $quiz->id,
                            'cron_type' => 'quiz_winner_leaderboard',
                        ],
                        [
                            'tenant_id' => $quiz->tenant_id ?? 1,
                            'scheduled_at' => $scheduledAt,
                            'status' => 'pending',
                        ]
                    );
                }
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'quiz_question')
            ->withPivot(
                'order', 'bot_correct_answer', 'is_switch_question',
                'question_text', 'question_audio', 'type', 'media_url', 'options',
                'correct_answer', 'hindi_question', 'hindi_options', 'hindi_correct_answer',
                'audio_file', 'explanation', 'category_id', 'sub_category',
                'difficulty', 'language', 'time_limit_seconds', 'start_time',
                'end_time', 'xp_value', 'negative_marking', 'status', 'tags',
                'source', 'moderation_status'
            )
            ->withTimestamps()
            ->orderBy('quiz_question.order');
    }
    // app/Models/Quiz.php

    public function rankReward()
    {
        return $this->hasOne(QuizRankReward::class, 'quiz_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function rankRewards()
    {
        return $this->hasMany(QuizRankReward::class, 'quiz_id');
    }

    public function filter()
    {
        return $this->hasOne(QuizFilter::class, 'quiz_id');
    }


    public function level()
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    public function quizType()
    {
        return $this->belongsTo(QuizType::class, 'type');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

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
    // ✅ YE NAYA FUNCTION ADD KAREIN (Yahi missing tha)
    public function quizQuestions()
    {
        return $this->hasMany(QuizQuestion::class, 'quiz_id');
    }

    public function fffQuestions()
    {
        return $this->questions();
    }

    public function tempQuizQuestions()
    {
        return $this->questions();
    }

    public function tempQuizStatus()
    {
        return $this->quizStatus();
    }

    public function tempQuizUsers()
    {
        return $this->hasMany(\App\Models\TempQuizUser::class, 'quiz_id');
    }

    /**
     * Encrypt a quiz ID into a URL-safe string.
     */
    public static function encryptId($id): string
    {
        $encrypted = Crypt::encryptString((string) $id);
        return str_replace(['+', '/', '='], ['-', '_', '~'], $encrypted);
    }

    /**
     * Decrypt an encrypted quiz ID string.
     */
    public static function decryptId($encrypted): ?string
    {
        if (empty($encrypted)) {
            return null;
        }
        try {
            $raw = str_replace(['-', '_', '~'], ['+', '/', '='], (string) $encrypted);
            return Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Resolve a Quiz instance from an ID, encrypted string, or existing Quiz model instance.
     */
    public static function resolveQuiz($idOrEncrypted): Quiz
    {
        if ($idOrEncrypted instanceof self) {
            return $idOrEncrypted;
        }

        $decrypted = static::decryptId($idOrEncrypted);
        if (!empty($decrypted) && is_numeric($decrypted)) {
            return static::findOrFail((int) $decrypted);
        }

        if (is_numeric($idOrEncrypted)) {
            return static::findOrFail((int) $idOrEncrypted);
        }

        abort(404, 'Quiz not found.');
    }

    /**
     * Encrypted ID accessor ($quiz->encrypted_id).
     */
    public function getEncryptedIdAttribute(): string
    {
        return static::encryptId($this->id);
    }

    /**
     * Get the value of the model's route key (uses encrypted ID in generated route URLs).
     */
    public function getRouteKey()
    {
        return $this->encrypted_id;
    }

    /**
     * Retrieve the model for a bound value (supports both encrypted string and numeric ID).
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $decrypted = static::decryptId($value);
        if (!empty($decrypted) && is_numeric($decrypted)) {
            return $this->where($field ?? $this->getRouteKeyName(), (int) $decrypted)->firstOrFail();
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)->firstOrFail();
    }

    /**
     * Get the scheduled end of the question and review stages.
     */
    public function getAnswerEndTime(?int $userId = null): ?\Carbon\Carbon
    {
        $scheduledAt = $this->scheduled_at 
            ? \Carbon\Carbon::parse($this->scheduled_at) 
            : ($this->availability_start ? \Carbon\Carbon::parse($this->availability_start) : null);

        if (!$scheduledAt && $this->id) {
            $counterStartMs = Cache::get("quiz_{$this->id}_counter_start_ms");
            if (is_numeric($counterStartMs)) {
                $scheduledAt = \App\Services\QuizTimeline::fromTimestampMilliseconds((int) $counterStartMs);
            }
        }

        if (!$scheduledAt) {
            return null;
        }

        if ($this->visibility === 'fastest_finger_first') {
            return $scheduledAt->copy()->addSeconds(80);
        }

        $questionCount = (!empty($this->question_limit) ? (int) $this->question_limit : null)
            ?: QuizQuestion::where('quiz_id', $this->id)
            ->where(function ($query) {
                $query->whereNull('is_switch_question')->orWhere('is_switch_question', 0);
            })
            ->count();
        $questionCount = $questionCount ?: ((!empty($this->question_limit) ? (int) $this->question_limit : null) ?: 10);
        $switchUsed = $userId
            ? (bool) QuizUser::where('quiz_id', $this->id)->where('user_id', $userId)->value('switch_option')
            : (bool) $this->switch_option;

        return \App\Services\QuizTimeline::answerSheetAt(
            $scheduledAt,
            $questionCount + ($switchUsed ? 1 : 0)
        );
    }

    /**
     * Check if quiz answer submission time has finished.
     */
    public function isAnswerTimeFinished(?int $userId = null): bool
    {
        $endTime = $this->getAnswerEndTime($userId);
        if (!$endTime) {
            return false;
        }

        return now()->gte($endTime);
    }

    /**
     * Check if any quiz is currently live/playing or calculating winners.
     * From quiz scheduled time until quiz end / winners declared (complete_status = completed),
     * heavy background crons (WhatsApp, Firebase, Redeem Alerts, Referral Rewards, Leaderboard Update)
     * are paused to prevent CPU spike and MySQL locks during active gameplay.
     */
    public static function isAnyQuizPlayingNow(): bool
    {
        return \Illuminate\Support\Facades\Cache::remember('is_any_quiz_playing_now', 5, function () {
            $now = now();

            return self::where(function ($query) use ($now) {
                // 1. Winner calculation or play data processing in progress (only recent within 15 minutes, not stale old quizzes)
                $query->where('complete_status', 'processing')
                    ->where(function ($pq) use ($now) {
                        $pq->where('updated_at', '>=', $now->copy()->subMinutes(15))
                           ->orWhere('scheduled_at', '>=', $now->copy()->subMinutes(20));
                    })
                    // 2. OR Quiz has started (scheduled_at <= now) and is not yet completed
                    ->orWhere(function ($q) use ($now) {
                        $q->where('complete_status', 'pending')
                            ->where('approve_status', 'approved')
                            ->where(function ($timeQ) use ($now) {
                                $timeQ->where(function ($sq) use ($now) {
                                    $sq->whereNotNull('scheduled_at')
                                       ->where('scheduled_at', '<=', $now)
                                       ->where('scheduled_at', '>=', $now->copy()->subMinutes(60));
                                })->orWhere(function ($aq) use ($now) {
                                    $aq->whereNotNull('availability_start')
                                       ->where('availability_start', '<=', $now)
                                       ->where('availability_start', '>=', $now->copy()->subMinutes(60));
                                });
                            });
                    });
            })->exists();
        });
    }
}
