<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'quiz_status';

    protected $fillable = [
        'tenant_id',
        'quiz_id',
        'total_enroll_user',
        'total_played_user',
        'total_correct_ans',
        'total_complete_quiz_user',
        'total_intrested_user',
        'correct_answer_average_time',
        'current_question_id',
        'status',
    ];

    /**
     * Model events: Automatically sync data to Session and Cache whenever created or updated.
     */
    protected static function booted()
    {
        static::saved(function ($quizStatus) {
            if ($quizStatus->quiz_id) {
                static::syncToSessionAndCache($quizStatus->quiz_id, $quizStatus->toArray());
            }
        });

        static::deleted(function ($quizStatus) {
            if ($quizStatus->quiz_id) {
                Session::forget("quiz_{$quizStatus->quiz_id}_status");
                Cache::forget("quiz_{$quizStatus->quiz_id}_status");
            }
        });
    }

    /**
     * Cache in-memory of ensured quiz status IDs to avoid redundant DB reads
     */
    protected static array $ensuredQuizzes = [];
    protected static array $memoryCache = [];

    /**
     * Ensure QuizStatus record exists for a quiz.
     */
    public static function ensureRecordForQuiz($quizId, $tenantId = null)
    {
        if (isset(static::$ensuredQuizzes[$quizId])) {
            return static::$ensuredQuizzes[$quizId];
        }

        if (!empty(static::$memoryCache["ensured_{$quizId}"])) {
            static::$ensuredQuizzes[$quizId] = true;
            return true;
        }

        $cacheKey = "quiz_{$quizId}_status_ensured";
        if (Cache::has($cacheKey)) {
            static::$memoryCache["ensured_{$quizId}"] = true;
            static::$ensuredQuizzes[$quizId] = true;
            return true;
        }

        $quiz = Quiz::find($quizId);
        $resolvedTenantId = $tenantId ?? $quiz?->tenant_id ?? 1;
        $resolvedStatus = $quiz?->complete_status ?? 'pending';

        $record = static::firstOrCreate(
            ['quiz_id' => $quizId],
            [
                'tenant_id'                   => $resolvedTenantId,
                'total_enroll_user'           => 0,
                'total_played_user'           => 0,
                'total_complete_quiz_user'    => 0,
                'total_correct_ans'           => 0,
                'total_intrested_user'        => 0,
                'correct_answer_average_time' => 0,
                'status'                      => $resolvedStatus,
            ]
        );

        Cache::put($cacheKey, true, 86400);
        static::$memoryCache["ensured_{$quizId}"] = true;
        static::$ensuredQuizzes[$quizId] = $record;

        return $record;
    }

    /**
     * Combined atomic update for interested and enrolled user count in a single query.
     */
    public static function recordInterestedAndEnrolledUser($quizId, $tenantId = null, $isEnrolled = true)
    {
        try {
            static::ensureRecordForQuiz($quizId, $tenantId);

            if ($isEnrolled) {
                DB::table('quiz_status')->where('quiz_id', $quizId)->update([
                    'total_intrested_user' => DB::raw('total_intrested_user + 1'),
                    'total_enroll_user'    => DB::raw('total_enroll_user + 1'),
                ]);
            } else {
                DB::table('quiz_status')->where('quiz_id', $quizId)->increment('total_intrested_user');
            }
            return true;
        } catch (\Throwable $e) {
            Log::error("QuizStatus recordInterestedAndEnrolledUser error: " . $e->getMessage(), ['quiz_id' => $quizId]);
            return null;
        }
    }

    /**
     * Update total_intrested_user when a user clicks join or initiates joining.
     * Uses ultra-fast atomic increment for high concurrency.
     */
    public static function recordInterestedUser($quizId, $tenantId = null)
    {
        try {
            static::ensureRecordForQuiz($quizId, $tenantId);
            DB::table('quiz_status')->where('quiz_id', $quizId)->increment('total_intrested_user');
            return true;
        } catch (\Throwable $e) {
            Log::error("QuizStatus recordInterestedUser error: " . $e->getMessage(), ['quiz_id' => $quizId]);
            return null;
        }
    }

    /**
     * Update total_enroll_user when a user successfully joins / enrolls in the quiz.
     * Uses ultra-fast atomic increment for high concurrency.
     */
    public static function recordEnrolledUser($quizId, $tenantId = null)
    {
        try {
            static::ensureRecordForQuiz($quizId, $tenantId);
            DB::table('quiz_status')->where('quiz_id', $quizId)->increment('total_enroll_user');
            return true;
        } catch (\Throwable $e) {
            Log::error("QuizStatus recordEnrolledUser error: " . $e->getMessage(), ['quiz_id' => $quizId]);
            return null;
        }
    }

    /**
     * Handle user answer submission for total_played_user and total_complete_quiz_user.
     * Uses in-memory caching and milestone-based atomic DB updates to eliminate row lock contention.
     */
    public static function handleAnswerSubmitted($quizId, $userId, $isCorrect = false)
    {
        try {
            static::ensureRecordForQuiz($quizId);

            // 1. Determine question limit using static memory first (0ms)
            if (!isset(static::$memoryCache["quiz_{$quizId}_qlimit"])) {
                static::$memoryCache["quiz_{$quizId}_qlimit"] = (int) Cache::remember("quiz_{$quizId}_qlimit", 600, function () use ($quizId) {
                    $totalStandardQuestions = QuizQuestion::where('quiz_id', $quizId)
                        ->where(function ($q) {
                            $q->where('is_switch_question', 0)->orWhereNull('is_switch_question');
                        })
                        ->count();

                    $quiz = Quiz::find($quizId);
                    $limit = $totalStandardQuestions > 0 ? $totalStandardQuestions : ($quiz?->question_limit ?? 10);
                    if ($quiz && !empty($quiz->question_limit) && (int) $quiz->question_limit > 0 && (int) $quiz->question_limit < $limit) {
                        $limit = (int) $quiz->question_limit;
                    }
                    return (int) $limit;
                });
            }
            $questionLimit = static::$memoryCache["quiz_{$quizId}_qlimit"];

            // 2. Pure in-memory check for total_played_user (0ms):
            $userKey = "{$quizId}_{$userId}";
            $isFirstAnswerForUser = false;
            if (!isset(static::$memoryCache["p_{$userKey}"])) {
                static::$memoryCache["p_{$userKey}"] = true;
                $isFirstAnswerForUser = Cache::add("quiz_{$quizId}_u_{$userId}_played", 1, 86400);
            }

            // 3. Pure in-memory check for total_complete_quiz_user (0ms):
            $isQuizCompletedForUser = false;
            if (!isset(static::$memoryCache["c_{$userKey}"])) {
                static::$memoryCache["cnt_{$userKey}"] = (static::$memoryCache["cnt_{$userKey}"] ?? 0) + 1;
                if (static::$memoryCache["cnt_{$userKey}"] >= $questionLimit) {
                    static::$memoryCache["c_{$userKey}"] = true;
                    $isQuizCompletedForUser = Cache::add("quiz_{$quizId}_u_{$userId}_completed", 1, 86400);
                }
            }

            // 4. Atomic updates only when milestone reached (avoids row lock contention on row 409):
            $updates = [];
            if ($isFirstAnswerForUser) {
                $updates['total_played_user'] = DB::raw('total_played_user + 1');
            }
            if ($isQuizCompletedForUser) {
                $updates['total_complete_quiz_user'] = DB::raw('total_complete_quiz_user + 1');
            }

            if (!empty($updates)) {
                DB::table('quiz_status')->where('quiz_id', $quizId)->update($updates);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("QuizStatus handleAnswerSubmitted error: " . $e->getMessage(), [
                'quiz_id' => $quizId,
                'user_id' => $userId,
            ]);
            return null;
        }
    }

    /**
     * Recalculate and sync all counts from database tables.
     */
    public static function syncQuizCounts($quizId)
    {
        try {
            $quizStatus = static::ensureRecordForQuiz($quizId);
            $quiz = Quiz::find($quizId);

            $totalStandardQuestions = QuizQuestion::where('quiz_id', $quizId)
                ->where(function ($q) {
                    $q->where('is_switch_question', 0)->orWhereNull('is_switch_question');
                })
                ->count();

            $questionLimit = $totalStandardQuestions > 0 ? $totalStandardQuestions : ($quiz?->question_limit ?? 10);
            if ($quiz && !empty($quiz->question_limit) && (int) $quiz->question_limit > 0 && (int) $quiz->question_limit < $questionLimit) {
                $questionLimit = (int) $quiz->question_limit;
            }

            $interestedCount = QuizUser::where('quiz_id', $quizId)
                ->distinct('user_id')
                ->count('user_id');

            $enrolledCount = QuizUser::where('quiz_id', $quizId)
                ->where(function ($q) {
                    $q->where('status', 'active')->orWhereNull('status');
                })
                ->distinct('user_id')
                ->count('user_id');

            $playedCount = QuizQuestionUserAnswer::where('quiz_id', $quizId)
                ->whereNotNull('answer')
                ->where('answer', '!=', '')
                ->distinct('user_id')
                ->count('user_id');

            $completedCount = QuizQuestionUserAnswer::where('quiz_id', $quizId)
                ->whereNotNull('answer')
                ->where('answer', '!=', '')
                ->select('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(DISTINCT question_id) >= ?', [$questionLimit])
                ->get()
                ->count();

            $totalCorrect = QuizQuestionUserAnswer::where('quiz_id', $quizId)
                ->where('status', 1)
                ->count();

            $status = $quiz?->complete_status ?? $quizStatus->status ?? 'pending';

            $quizStatus->update([
                'total_intrested_user'     => $interestedCount,
                'total_enroll_user'        => $enrolledCount,
                'total_played_user'        => $playedCount,
                'total_complete_quiz_user' => $completedCount,
                'total_correct_ans'        => $totalCorrect,
                'status'                   => $status,
            ]);

            $quizStatus->refresh();
            static::syncToSessionAndCache($quizId, $quizStatus->toArray());
            return $quizStatus;
        } catch (\Throwable $e) {
            Log::error("QuizStatus syncQuizCounts error: " . $e->getMessage(), ['quiz_id' => $quizId]);
            return null;
        }
    }

    /**
     * Store/sync quiz_status array to Session and Cache
     */
    public static function syncToSessionAndCache($quizId, array $data)
    {
        Session::put("quiz_{$quizId}_status", $data);
        Cache::put("quiz_{$quizId}_status", $data, now()->addHours(2));
    }

    /**
     * Retrieve status from Session.
     * If not in Session, check Cache or DB, store in Session, and return.
     */
    public static function getStatusFromSession($quizId)
    {
        $sessionKey = "quiz_{$quizId}_status";

        if (Session::has($sessionKey) && !empty(Session::get($sessionKey))) {
            return Session::get($sessionKey);
        }

        if (Cache::has($sessionKey)) {
            $cached = Cache::get($sessionKey);
            Session::put($sessionKey, $cached);
            return $cached;
        }

        $dbStatus = static::where('quiz_id', $quizId)->first();
        if ($dbStatus) {
            $data = $dbStatus->toArray();
            static::syncToSessionAndCache($quizId, $data);
            return $data;
        }

        return null;
    }

    /**
     * Helper to update quiz_status table and immediately update session
     */
    public static function updateAndSync($quizId, array $attributes)
    {
        $quizStatus = static::where('quiz_id', $quizId)->first();
        if ($quizStatus) {
            $quizStatus->update($attributes);
            static::syncToSessionAndCache($quizId, $quizStatus->fresh()->toArray());
        }
        return $quizStatus;
    }

    /**
     * Helper to increment column and immediately update session
     */
    public static function incrementAndSync($quizId, string $column, int $amount = 1)
    {
        $quizStatus = static::where('quiz_id', $quizId)->first();
        if ($quizStatus) {
            $quizStatus->increment($column, $amount);
            static::syncToSessionAndCache($quizId, $quizStatus->fresh()->toArray());
        }
        return $quizStatus;
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}