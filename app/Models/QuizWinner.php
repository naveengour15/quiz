<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizWinner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'quiz_id',
        'user_id',
        'total_question',
        'total_correct_answer',
        'total_time',
        'per_ans_average_time',
        'rank',
        'level',
        'reward_type',
        'reward',
        'reward_id',
        'voucher_history_id',
        'reward_detail',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'total_time' => 'double',
        'per_ans_average_time' => 'double',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

     // NEW: Relation to QuizRankReward using reward_id
    public function rankReward()
    {
        return $this->belongsTo(QuizRankReward::class, 'reward_id');
    }

     public function voucherHistory()
    {
        return $this->belongsTo(VoucherHistory::class);
    }

    /**
     * Recalculates and updates the ranks for all participants of a quiz.
     * Prioritizes total_correct_answer DESC, then total_time ASC.
     * Optimized for high concurrency: Uses a single atomic SQL window query on MySQL 8+,
     * or single batch CASE statement on other drivers, completely eliminating row-by-row loops.
     */
    public static function recalculateRanks(int $quizId): void
    {
        $lockKey = "quiz_{$quizId}_recalc_ranks_lock";
        // Debounce / throttle: Only 1 process can recalculate ranks at a time (held for 15 seconds)
        if (!app()->runningUnitTests() && !\Illuminate\Support\Facades\Cache::add($lockKey, 1, 15)) {
            return;
        }

        try {
            // 1. Delete any participants who did not attempt at least 1 question
            static::where('quiz_id', $quizId)
                ->where(function ($q) {
                    $q->where('total_question', '<', 1)
                      ->orWhereNull('total_question');
                })
                ->delete();

            // 2. Only users who actually submitted at least 1 answer AND are actively enrolled in the quiz qualify
            $hasQuizUsers = \Illuminate\Support\Facades\Schema::hasTable('quiz_users') && \App\Models\QuizUser::where('quiz_id', $quizId)->exists();
            if ($hasQuizUsers) {
                // Perform purge via SQL subqueries without loading tens of thousands of IDs into PHP memory
                static::where('quiz_id', $quizId)
                    ->where(function ($q) use ($quizId) {
                        $q->whereNotIn('user_id', function ($sub) use ($quizId) {
                            $sub->select('user_id')
                                ->from('quiz_question_user_answers')
                                ->where('quiz_id', $quizId)
                                ->whereNotNull('answer')
                                ->where('answer', '!=', '');
                        })
                        ->orWhereNotIn('user_id', function ($sub) use ($quizId) {
                            $sub->select('user_id')
                                ->from('quiz_users')
                                ->where('quiz_id', $quizId)
                                ->where('status', 'active');
                        });
                    })
                    ->delete();
            }

            // 3. Fast Atomic Update on MySQL 8+ (Single Query Execution)
            $driver = \Illuminate\Support\Facades\DB::getDriverName();
            if ($driver === 'mysql') {
                \Illuminate\Support\Facades\DB::statement("
                    UPDATE quiz_winners qw
                    JOIN (
                        SELECT id, RANK() OVER (ORDER BY total_correct_answer DESC, ROUND(total_time, 3) ASC) as calculated_rank
                        FROM quiz_winners
                        WHERE quiz_id = ? AND total_question >= 1
                    ) ranked ON qw.id = ranked.id
                    SET qw.rank = ranked.calculated_rank
                    WHERE qw.rank IS NULL OR qw.rank != ranked.calculated_rank
                ", [$quizId]);
                return;
            }

            // 4. Fallback for SQLite / Non-MySQL: Batch CASE UPDATE (1 query instead of N queries)
            $winners = static::where('quiz_id', $quizId)
                ->where('total_question', '>=', 1)
                ->orderByDesc('total_correct_answer')
                ->orderBy('total_time')
                ->orderBy('id')
                ->get();

            if ($winners->isEmpty()) {
                return;
            }

            $currentRank = 1;
            $prevCorrect = null;
            $prevTime = null;
            $updates = [];

            foreach ($winners as $idx => $winner) {
                $c = (int) ($winner->total_correct_answer ?? 0);
                $t = round((float) ($winner->total_time ?? 0), 3);

                if ($prevCorrect !== null && $c === $prevCorrect && $t === $prevTime) {
                    // Tied with previous user -> keep same rank
                } else {
                    $currentRank = $idx + 1;
                }

                $prevCorrect = $c;
                $prevTime = $t;

                if ((int) $winner->rank !== $currentRank) {
                    $updates[$winner->id] = $currentRank;
                }
            }

            if (!empty($updates)) {
                // Execute bulk update in chunks of 500 using a single CASE statement
                foreach (array_chunk($updates, 500, true) as $chunk) {
                    $caseSql = "CASE id ";
                    $ids = [];
                    foreach ($chunk as $winnerId => $rankVal) {
                        $caseSql .= "WHEN " . (int)$winnerId . " THEN " . (int)$rankVal . " ";
                        $ids[] = (int)$winnerId;
                    }
                    $caseSql .= "END";
                    $idsStr = implode(',', $ids);

                    \Illuminate\Support\Facades\DB::statement(
                        "UPDATE quiz_winners SET rank = {$caseSql} WHERE id IN ({$idsStr})"
                    );
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("recalculateRanks error for quiz {$quizId}: " . $e->getMessage());
        }
    }
}