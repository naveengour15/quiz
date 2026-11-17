<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionUserAnswer;
use App\Models\QuizWinner;
use App\Models\QuizRankReward;
use App\Models\QuizUserEnrollment;
use App\Models\QuizUser;
use App\Models\User;
use App\Models\WalletHistory;
use Carbon\Carbon;

class RedisQuizService
{
    protected static ?bool $isRedisAvailable = null;
    protected static int $defaultTtl = 14400; // 4 hours

    /**
     * Test if Redis server is reachable and active.
     * Uses static in-memory caching per request lifecycle and short-lived file cache
     * across requests to eliminate 2+ second connection retry hangs when Redis is unavailable.
     */
    public static function isAvailable(): bool
    {
        if (self::$isRedisAvailable !== null) {
            return self::$isRedisAvailable;
        }

        // Fast cross-request check: eliminates repeated 2-second connection timeouts
        try {
            $cachedStatus = \Illuminate\Support\Facades\Cache::store('file')->get('fsr_redis_available_status');
            if ($cachedStatus !== null) {
                self::$isRedisAvailable = (bool) $cachedStatus;
                return self::$isRedisAvailable;
            }
        } catch (\Throwable $e) {}

        try {
            config([
                'database.redis.default.timeout' => 0.5,
                'database.redis.default.read_timeout' => 0.5,
                'database.redis.default.max_retries' => 0,
            ]);

            $client = Redis::connection();
            $ping = $client->ping();
            self::$isRedisAvailable = ($ping === true || $ping === 'PONG' || $ping === '+PONG');

            try {
                \Illuminate\Support\Facades\Cache::store('file')->put('fsr_redis_available_status', self::$isRedisAvailable, now()->addSeconds(self::$isRedisAvailable ? 60 : 30));
            } catch (\Throwable $e) {}
        } catch (\Throwable $e) {
            self::$isRedisAvailable = false;
            Log::warning('Redis is unavailable; quiz operations will use the database fallback.', [
                'exception' => $e,
            ]);

            try {
                \Illuminate\Support\Facades\Cache::store('file')->put('fsr_redis_available_status', false, now()->addSeconds(30));
            } catch (\Throwable $e) {}
        }

        return self::$isRedisAvailable;
    }

    /**
     * Force reset the availability cache (e.g. after reconnection).
     */
    public static function resetAvailability(): void
    {
        self::$isRedisAvailable = null;
        try {
            \Illuminate\Support\Facades\Cache::store('file')->forget('fsr_redis_available_status');
        } catch (\Throwable $e) {}
    }

    /* -------------------------------------------------------------------------- */
    /* KEY GENERATION HELPERS                                                     */
    /* -------------------------------------------------------------------------- */

    public static function getPlayersKey(int $quizId): string
    {
        return "fsr:quiz:{$quizId}:players";
    }

    public static function getQuestionsKey(int $quizId): string
    {
        return "fsr:quiz:{$quizId}:questions";
    }

    public static function getSwitchQuestionsKey(int $quizId): string
    {
        return "fsr:quiz:{$quizId}:switch_questions";
    }

    public static function getUserAnswersKey(int $quizId, int $userId): string
    {
        return "fsr:quiz:{$quizId}:user:{$userId}:answers";
    }

    public static function getUserMetaKey(int $quizId, int $userId): string
    {
        return "fsr:quiz:{$quizId}:user:{$userId}:meta";
    }

    public static function getLeaderboardKey(int $quizId): string
    {
        return "fsr:quiz:{$quizId}:leaderboard";
    }

    public static function getSubmitLockKey(int $quizId, int $userId, int $questionId): string
    {
        return "fsr:quiz:{$quizId}:lock:{$userId}:{$questionId}";
    }

    /* -------------------------------------------------------------------------- */
    /* 1. QUIZ JOIN & PRELOAD                                                     */
    /* -------------------------------------------------------------------------- */

    public const PENDING_ENROLLMENTS_QUEUE = 'fsr:quiz:pending_enrollments_queue';

    /**
     * Check if a user is already enrolled into the quiz in-memory (0ms check).
     */
    public static function isUserEnrolled(int $quizId, int $userId): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        try {
            $key = self::getPlayersKey($quizId);
            return (bool) Redis::sismember($key, $userId);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Enroll a user into the quiz in-memory and push to batch queue for MySQL bulk upsert.
     */
    public static function enrollUserWithQueue(
        int $quizId,
        int $userId,
        int $tenantId = 1,
        string $status = 'active',
        string $outStatus = 'waiting'
    ): bool {
        if (!self::isAvailable()) {
            return false;
        }

        try {
            $playersKey = self::getPlayersKey($quizId);
            $nowStr = now()->toDateTimeString();

            $payload = json_encode([
                'tenant_id'  => $tenantId,
                'quiz_id'    => $quizId,
                'user_id'    => $userId,
                'status'     => $status,
                'out_status' => $outStatus,
                'created_at' => $nowStr,
                'updated_at' => $nowStr,
            ]);

            Redis::pipeline(function ($pipe) use ($playersKey, $userId, $payload) {
                // 1. Add to players set (guarantees uniqueness and 0ms membership check)
                $pipe->sadd($playersKey, $userId);
                $pipe->expire($playersKey, self::$defaultTtl);

                // 2. Push to pending enrollments queue for bulk MySQL upsert
                $pipe->rpush(self::PENDING_ENROLLMENTS_QUEUE, $payload);
            });

            return true;
        } catch (\Throwable $e) {
            Log::error("RedisQuizService::enrollUserWithQueue failed for quiz {$quizId}, user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enroll a user into the quiz in-memory.
     */
    public static function enrollUser(int $quizId, int $userId): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        try {
            $key = self::getPlayersKey($quizId);
            Redis::sadd($key, $userId);
            Redis::expire($key, self::$defaultTtl);
            return true;
        } catch (\Throwable $e) {
            Log::error("RedisQuizService::enrollUser failed for quiz {$quizId}, user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unenroll a user from the quiz in-memory.
     */
    public static function unenrollUser(int $quizId, int $userId): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        try {
            $key = self::getPlayersKey($quizId);
            Redis::srem($key, $userId);
            return true;
        } catch (\Throwable $e) {
            Log::error("RedisQuizService::unenrollUser failed for quiz {$quizId}, user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Return total enrolled players count from Redis.
     */
    public static function getEnrolledPlayersCount(int $quizId): int
    {
        if (!self::isAvailable()) {
            return 0;
        }

        try {
            return (int) Redis::scard(self::getPlayersKey($quizId));
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Cache full questions and switch questions payload in Redis.
     */
    public static function cacheQuizQuestions(int $quizId, array $questions, array $switchQuestions = []): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        try {
            $qKey = self::getQuestionsKey($quizId);
            $swKey = self::getSwitchQuestionsKey($quizId);

            Redis::setex($qKey, self::$defaultTtl, json_encode($questions));
            Redis::setex($swKey, self::$defaultTtl, json_encode($switchQuestions));

            return true;
        } catch (\Throwable $e) {
            Log::error("RedisQuizService::cacheQuizQuestions failed for quiz {$quizId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve preloaded questions from Redis.
     */
    public static function getQuizQuestions(int $quizId): ?array
    {
        if (!self::isAvailable()) {
            return null;
        }

        try {
            $raw = Redis::get(self::getQuestionsKey($quizId));
            if (!$raw) {
                return null;
            }

            $questions = json_decode($raw, true);
            $rawSwitch = Redis::get(self::getSwitchQuestionsKey($quizId));
            $switchQuestions = $rawSwitch ? json_decode($rawSwitch, true) : [];

            return [
                'questions' => is_array($questions) ? $questions : [],
                'switch_questions' => is_array($switchQuestions) ? $switchQuestions : [],
            ];
        } catch (\Throwable $e) {
            Log::error("RedisQuizService::getQuizQuestions failed for quiz {$quizId}: " . $e->getMessage());
            return null;
        }
    }

    /* -------------------------------------------------------------------------- */
    /* 2. REAL-TIME ANSWER SUBMISSION & SCORING                                   */
    public const PENDING_ANSWERS_QUEUE = 'fsr:quiz:pending_answers_queue';

    /* -------------------------------------------------------------------------- */
    /* 2. REAL-TIME ANSWER SUBMISSION & SCORING                                   */
    /* -------------------------------------------------------------------------- */

    /**
     * Record a user's answer into Redis Hash and update Redis ZSET Leaderboard.
     * Uses Redis Pipeline to execute all writes in a single TCP network round-trip,
     * stores only minimal payload, and pre-calculates composite score in memory.
     * Also pushes answer payload into PENDING_ANSWERS_QUEUE for batch collection into MySQL.
     */
    public static function recordAnswer(
        int $quizId,
        int $userId,
        int $questionId,
        string $answer,
        bool $isCorrect,
        float $totalTime,
        ?array $questionData = null,
        ?string $correctAnsStr = null,
        int $tenantId = 1,
        ?string $showTime = null,
        bool $pushToQueue = true
    ): array {
        if (!self::isAvailable()) {
            return ['recorded' => false, 'already_answered' => false];
        }

        try {
            $answersKey = self::getUserAnswersKey($quizId, $userId);
            $metaKey = self::getUserMetaKey($quizId, $userId);

            // Single pipeline round-trip to read existing answer & current user meta
            $readResults = Redis::pipeline(function ($pipe) use ($answersKey, $metaKey, $questionId) {
                $pipe->hget($answersKey, (string) $questionId);
                $pipe->hgetall($metaKey);
            });

            $existingJson = $readResults[0] ?? null;
            $meta = $readResults[1] ?? [];

            // Check if already answered in Redis
            if ($existingJson) {
                $existing = json_decode($existingJson, true);
                return [
                    'recorded' => true,
                    'already_answered' => true,
                    'answer' => $existing['answer'] ?? $answer,
                    'is_correct' => (bool) ($existing['is_correct'] ?? $isCorrect),
                    'total_time' => (float) ($existing['total_time'] ?? $totalTime),
                    'correct_answer' => $existing['correct_answer'] ?? $correctAnsStr,
                ];
            }

            $answerTime = Carbon::now();
            $formattedTotalTime = (float) number_format($totalTime, 3, '.', '');

            // Store minimal payload in hash
            $answerPayload = [
                'tenant_id'   => $tenantId,
                'quiz_id'     => $quizId,
                'user_id'     => $userId,
                'question_id' => $questionId,
                'answer'      => $answer,
                'is_correct'  => $isCorrect,
                'status'      => $isCorrect ? 1 : 0,
                'show_time'   => $showTime,
                'ans_time'    => $answerTime->format('H:i:s.v'),
                'total_time'  => $formattedTotalTime,
                'created_at'  => $answerTime->toDateTimeString(),
            ];

            // Pre-calculate user stats in memory
            $prevTotalQ = (int) ($meta['total_questions'] ?? 0);
            $prevCorrect = (int) ($meta['correct_count'] ?? 0);
            $prevTotalTime = (float) ($meta['total_time'] ?? 0.0);

            $newTotalQ = $prevTotalQ + 1;
            $newCorrectCount = $prevCorrect + ($isCorrect ? 1 : 0);
            $newTotalTime = (float) number_format($prevTotalTime + $formattedTotalTime, 3, '.', '');

            // Pre-calculate composite score in memory for Leaderboard ZSET
            $compositeScore = self::calculateCompositeScore($newCorrectCount, $newTotalTime);

            $playersKey = self::getPlayersKey($quizId);
            $leaderboardKey = self::getLeaderboardKey($quizId);
            $answerJson = json_encode($answerPayload);
            $ttl = self::$defaultTtl;

            // Execute all write operations in a single Redis::pipeline() block (1 TCP round-trip)
            Redis::pipeline(function ($pipe) use (
                $answersKey,
                $metaKey,
                $playersKey,
                $leaderboardKey,
                $questionId,
                $answerJson,
                $userId,
                $newTotalQ,
                $newCorrectCount,
                $newTotalTime,
                $compositeScore,
                $ttl,
                $pushToQueue
            ) {
                // 1. Save minimal answer payload in user's answers hash
                $pipe->hset($answersKey, (string) $questionId, $answerJson);
                $pipe->expire($answersKey, $ttl);

                // 2. Register user in players set
                $pipe->sadd($playersKey, $userId);

                // 3. Save updated user meta in hash
                $pipe->hset($metaKey, 'total_questions', $newTotalQ);
                $pipe->hset($metaKey, 'correct_count', $newCorrectCount);
                $pipe->hset($metaKey, 'total_time', $newTotalTime);
                $pipe->expire($metaKey, $ttl);

                // 4. Update Leaderboard Sorted Set
                $pipe->zadd($leaderboardKey, $compositeScore, (string) $userId);
                $pipe->expire($leaderboardKey, $ttl);

                // 5. Push to Pending Answers Queue for background batch collector (if not already persisted)
                if ($pushToQueue) {
                    $pipe->rpush(self::PENDING_ANSWERS_QUEUE, $answerJson);
                }
            });

            return [
                'recorded' => true,
                'already_answered' => false,
                'answer' => $answer,
                'is_correct' => $isCorrect,
                'total_time' => $formattedTotalTime,
                'correct_answer' => $correctAnsStr,
                'correct_count' => $newCorrectCount,
            ];
        } catch (\Throwable $e) {
            self::$isRedisAvailable = false;
            Log::error("RedisQuizService::recordAnswer failed for quiz {$quizId}, user {$userId}: " . $e->getMessage());
            return ['recorded' => false, 'already_answered' => false];
        }
    }

    /**
     * Compute composite score for Redis ZSET leaderboard.
     * Primary sort: correct answers count (descending)
     * Secondary tie-breaker: total time in ms (ascending, fastest wins)
     */
    public static function calculateCompositeScore(int $correctCount, float $totalTime): float
    {
        $maxTimeMs = 9999999;
        $timeMs = min($maxTimeMs, (int) round($totalTime * 1000));
        return ((float) $correctCount * 10000000.0) + ($maxTimeMs - $timeMs);
    }

    /**
     * Record lifeline usage in Redis.
     */
    public static function recordLifeline(int $quizId, int $userId, string $lifeline, $questionId = null): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        try {
            $metaKey = self::getUserMetaKey($quizId, $userId);
            if ($lifeline === 'fifty_fifty') {
                Redis::hset($metaKey, 'fifty_fifty', 1);
            } elseif ($lifeline === 'switch_option' || $lifeline === 'switch') {
                Redis::hset($metaKey, 'switch_option', 1);
                if ($questionId) {
                    Redis::hset($metaKey, 'switched_question_id', $questionId);
                }
            }
            Redis::expire($metaKey, self::$defaultTtl);
            return true;
        } catch (\Throwable $e) {
            Log::error("RedisQuizService::recordLifeline failed: " . $e->getMessage());
            return false;
        }
    }

    /* -------------------------------------------------------------------------- */
    /* 3. INSTANT LEADERBOARD (ZSET)                                              */
    /* -------------------------------------------------------------------------- */

    /**
     * Fetch Top N winners + current user rank and score directly from Redis Sorted Set.
     */
    public static function getLeaderboard(int $quizId, ?int $currentUserId = null, int $limit = 0): ?array
    {
        if (!self::isAvailable()) {
            return null;
        }

        try {
            $lbKey = self::getLeaderboardKey($quizId);
            $end = ($limit > 0) ? ($limit - 1) : -1;
            $topRaw = Redis::zrevrange($lbKey, 0, $end, ['WITHSCORES' => true]);

            if (empty($topRaw)) {
                return null;
            }

            $userIds = array_map('intval', array_keys($topRaw));
            if ($currentUserId && !in_array($currentUserId, $userIds)) {
                $userIds[] = $currentUserId;
            }

            // Batch fetch user names
            $usersMap = User::whereIn('id', $userIds)->pluck('name', 'id')->toArray();

            // Fetch rank rewards for this quiz if available
            $rankRewards = QuizRankReward::where('quiz_id', $quizId)
                ->where('status', 'active')
                ->get();

            $winnersData = [];
            $rank = 1;

            foreach ($topRaw as $uidStr => $score) {
                $uid = (int) $uidStr;
                $metaKey = self::getUserMetaKey($quizId, $uid);
                $meta = Redis::hgetall($metaKey);

                $correctCount = (int) ($meta['correct_count'] ?? 0);
                if ($correctCount < 1) {
                    continue;
                }

                $totalQ = (int) ($meta['total_questions'] ?? 0);
                $totalTime = (float) ($meta['total_time'] ?? 0);
                $name = $usersMap[$uid] ?? "Player #{$uid}";

                // Calculate reward
                $rewardVal = (float) ($correctCount * 10);
                if ($rankRewards->isNotEmpty()) {
                    $rule = $rankRewards->first(fn($r) => $rank >= $r->start_rank && $rank <= $r->end_rank);
                    if ($rule) {
                        $rewardVal = (float) $rule->reward;
                    }
                }

                $winnersData[] = [
                    'rank' => $rank,
                    'user_id' => $uid,
                    'user_name' => $name,
                    'name' => $name,
                    'score' => $correctCount,
                    'correct' => $correctCount,
                    'total' => $totalQ,
                    'reward' => $rewardVal,
                    'points' => $rewardVal,
                    'total_time' => $totalTime,
                    'status' => 'active',
                    'is_current_user' => $currentUserId ? ($uid === $currentUserId) : false,
                ];

                $rank++;
            }

            // Current user rank
            $userRankData = null;
            if ($currentUserId) {
                $userZRank = Redis::zrevrank($lbKey, (string) $currentUserId);
                if ($userZRank !== null && $userZRank !== false) {
                    $uRank = (int) $userZRank + 1;
                    $meta = Redis::hgetall(self::getUserMetaKey($quizId, $currentUserId));
                    $correctCount = (int) ($meta['correct_count'] ?? 0);
                    $totalQ = (int) ($meta['total_questions'] ?? 0);
                    $totalTime = (float) ($meta['total_time'] ?? 0);
                    $name = $usersMap[$currentUserId] ?? 'You';

                    if ($correctCount >= 1) {
                        $rewardVal = (float) ($correctCount * 10);
                        if ($rankRewards->isNotEmpty()) {
                            $rule = $rankRewards->first(fn($r) => $uRank >= $r->start_rank && $uRank <= $r->end_rank);
                            if ($rule) {
                                $rewardVal = (float) $rule->reward;
                            }
                        }

                        $userRankData = [
                            'rank' => $uRank,
                            'user_id' => $currentUserId,
                            'user_name' => $name,
                            'name' => $name,
                            'score' => $correctCount,
                            'correct' => $correctCount,
                            'total' => $totalQ,
                            'reward' => $rewardVal,
                            'points' => $rewardVal,
                            'total_time' => $totalTime,
                            'status' => 'active',
                            'is_current_user' => true,
                        ];
                    } else {
                        $userRankData = [
                            'rank' => '-',
                            'user_id' => $currentUserId,
                            'user_name' => $name,
                            'name' => $name,
                            'score' => 0,
                            'correct' => 0,
                            'total' => $totalQ,
                            'reward' => 0,
                            'points' => 0,
                            'total_time' => $totalTime,
                            'status' => 'active',
                            'is_current_user' => true,
                        ];
                    }
                }
            }

            return [
                'winners' => $winnersData,
                'user_rank' => $userRankData,
                'total_participants' => count($winnersData),
                'from_redis' => true,
            ];
        } catch (\Throwable $e) {
            Log::error("RedisQuizService::getLeaderboard failed for quiz {$quizId}: " . $e->getMessage());
            return null;
        }
    }

    /* -------------------------------------------------------------------------- */
    /* 4. USER ANSWER SHEET RETRIEVAL                                             */
    /* -------------------------------------------------------------------------- */

    /**
     * Get user answers directly from Redis (primary cache source before MySQL query).
     */
    public static function getUserAnswers(int $quizId, int $userId): ?array
    {
        return self::getUserAnswerSheet($quizId, $userId);
    }

    public static function getUserAnswer(int $quizId, int $userId, int $questionId): ?array
    {
        if (!self::isAvailable()) {
            return null;
        }

        try {
            $answer = Redis::hget(self::getUserAnswersKey($quizId, $userId), (string) $questionId);
            if (!$answer) {
                return null;
            }

            $decoded = json_decode($answer, true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            self::$isRedisAvailable = false;
            Log::error("RedisQuizService::getUserAnswer failed for quiz {$quizId}, user {$userId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user answers sheet directly from Redis.
     */
    public static function getUserAnswerSheet(int $quizId, int $userId): ?array
    {
        if (!self::isAvailable()) {
            return null;
        }

        try {
            $answersKey = self::getUserAnswersKey($quizId, $userId);
            $rawAnswers = Redis::hgetall($answersKey);

            if (empty($rawAnswers)) {
                return null;
            }

            $formatted = [];
            foreach ($rawAnswers as $qId => $jsonStr) {
                $item = json_decode($jsonStr, true);
                if ($item) {
                    $formatted[$qId] = $item;
                }
            }

            return $formatted;
        } catch (\Throwable $e) {
            self::$isRedisAvailable = false;
            Log::error("RedisQuizService::getUserAnswerSheet failed for quiz {$quizId}, user {$userId}: " . $e->getMessage());
            return null;
        }
    }

    /* -------------------------------------------------------------------------- */
    /* 5. ASYNC / BATCH PERSISTENCE TO MYSQL                                      */
    /* -------------------------------------------------------------------------- */

    /**
     * Collect pending answers from Redis queue and bulk upsert into MySQL.
     * Atomically pops up to $batchLimit answers using a Lua script (with fallback) to prevent duplicate processing.
     * If DB upsert succeeds, items are removed (already trimmed by Lua).
     * If DB upsert fails, items are re-queued back to Redis to prevent data loss.
     *
     * @param int $batchLimit
     * @return int Number of answers processed
     */
    public static function collectAndBulkUpsertPendingAnswers(int $batchLimit = 2000): int
    {
        if (!self::isAvailable()) {
            return 0;
        }

        try {
            $rawItems = [];
            $lua = "local queue = KEYS[1]\nlocal limit = tonumber(ARGV[1])\nlocal items = redis.call('LRANGE', queue, 0, limit - 1)\nif #items > 0 then\n    redis.call('LTRIM', queue, #items, -1)\nend\nreturn items";

            try {
                $rawItems = Redis::eval($lua, 1, self::PENDING_ANSWERS_QUEUE, $batchLimit);
            } catch (\Throwable $luaEx) {
                // Fallback if eval is not permitted in Redis configuration
                $rawItems = Redis::lrange(self::PENDING_ANSWERS_QUEUE, 0, $batchLimit - 1);
                if (!empty($rawItems)) {
                    Redis::ltrim(self::PENDING_ANSWERS_QUEUE, count($rawItems), -1);
                }
            }

            if (empty($rawItems) || !is_array($rawItems)) {
                return 0;
            }

            $nowStr = now()->toDateTimeString();
            $deduped = [];

            foreach ($rawItems as $itemStr) {
                $item = json_decode((string) $itemStr, true);
                if (!$item || !isset($item['quiz_id'], $item['user_id'], $item['question_id'])) {
                    continue;
                }

                $tenantId = (int) ($item['tenant_id'] ?? 1);
                $quizId = (int) $item['quiz_id'];
                $userId = (int) $item['user_id'];
                $questionId = (int) $item['question_id'];

                // Deduplicate in batch: latest answer wins
                $compositeKey = "{$tenantId}_{$quizId}_{$userId}_{$questionId}";
                $deduped[$compositeKey] = [
                    'tenant_id'   => $tenantId,
                    'quiz_id'     => $quizId,
                    'user_id'     => $userId,
                    'question_id' => $questionId,
                    'answer'      => (string) ($item['answer'] ?? ''),
                    'status'      => !empty($item['is_correct']) || !empty($item['status']) ? 1 : 0,
                    'show_time'   => !empty($item['show_time']) ? $item['show_time'] : null,
                    'ans_time'    => $item['ans_time'] ?? '00:00:00',
                    'total_time'  => number_format((float) ($item['total_time'] ?? 0), 3, '.', ''),
                    'created_at'  => $item['created_at'] ?? $nowStr,
                    'updated_at'  => $nowStr,
                ];
            }

            if (empty($deduped)) {
                return 0;
            }

            $rows = array_values($deduped);
            // Chunk by 250 items instead of 1000 to avoid massive InnoDB index locks & thread contention
            $chunks = array_chunk($rows, 250);

            try {
                foreach ($chunks as $chunk) {
                    QuizQuestionUserAnswer::upsert(
                        $chunk,
                        ['tenant_id', 'quiz_id', 'user_id', 'question_id'],
                        [
                            'answer',
                            'status',
                            'show_time',
                            'ans_time',
                            'total_time',
                            'updated_at',
                            'deleted_at',
                        ]
                    );
                    if (count($chunks) > 1) {
                        usleep(5000); // 5ms breathing room for MySQL dirty page flush & CPU relief
                    }
                }
            } catch (\Throwable $dbEx) {
                // DB Failure: Re-push raw items back into Redis queue so zero data is lost
                Log::error("RedisQuizService::collectAndBulkUpsertPendingAnswers DB failure, requeueing items: " . $dbEx->getMessage());
                try {
                    Redis::pipeline(function ($pipe) use ($rawItems) {
                        foreach ($rawItems as $itemStr) {
                            $pipe->rpush(self::PENDING_ANSWERS_QUEUE, $itemStr);
                        }
                    });
                } catch (\Throwable $requeueEx) {}
                throw $dbEx;
            }

            return count($rows);
        } catch (\Throwable $e) {
            Log::error("RedisQuizService::collectAndBulkUpsertPendingAnswers error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Collect pending quiz enrollments from Redis queue and bulk upsert into MySQL.
     * Atomically pops up to $batchLimit items using Lua script to eliminate duplicate processing.
     * If DB upsert succeeds, items are removed.
     * If DB upsert fails, items are re-queued back to Redis to prevent data loss (Zero request skip).
     *
     * @param int $batchLimit
     * @return int Number of enrollments processed
     */
    public static function collectAndBulkUpsertPendingEnrollments(int $batchLimit = 2000): int
    {
        if (!self::isAvailable()) {
            return 0;
        }

        try {
            $rawItems = [];
            $lua = "local queue = KEYS[1]\nlocal limit = tonumber(ARGV[1])\nlocal items = redis.call('LRANGE', queue, 0, limit - 1)\nif #items > 0 then\n    redis.call('LTRIM', queue, #items, -1)\nend\nreturn items";

            try {
                $rawItems = Redis::eval($lua, 1, self::PENDING_ENROLLMENTS_QUEUE, $batchLimit);
            } catch (\Throwable $luaEx) {
                $rawItems = Redis::lrange(self::PENDING_ENROLLMENTS_QUEUE, 0, $batchLimit - 1);
                if (!empty($rawItems)) {
                    Redis::ltrim(self::PENDING_ENROLLMENTS_QUEUE, count($rawItems), -1);
                }
            }

            if (empty($rawItems) || !is_array($rawItems)) {
                return 0;
            }

            $nowStr = now()->toDateTimeString();
            $deduped = [];

            foreach ($rawItems as $itemStr) {
                $item = json_decode((string) $itemStr, true);
                if (!$item || !isset($item['quiz_id'], $item['user_id'])) {
                    continue;
                }

                $tenantId = (int) ($item['tenant_id'] ?? 1);
                $quizId = (int) $item['quiz_id'];
                $userId = (int) $item['user_id'];

                $compositeKey = "{$tenantId}_{$quizId}_{$userId}";
                $deduped[$compositeKey] = [
                    'tenant_id'  => $tenantId,
                    'quiz_id'    => $quizId,
                    'user_id'    => $userId,
                    'status'     => (string) ($item['status'] ?? 'active'),
                    'out_status' => (string) ($item['out_status'] ?? 'waiting'),
                    'created_at' => $item['created_at'] ?? $nowStr,
                    'updated_at' => $nowStr,
                ];
            }

            if (empty($deduped)) {
                return 0;
            }

            $rows = array_values($deduped);
            // Chunk by 250 items instead of 1000 to avoid massive InnoDB index locks & thread contention
            $chunks = array_chunk($rows, 250);

            try {
                foreach ($chunks as $chunk) {
                    \App\Models\QuizUser::upsert(
                        $chunk,
                        ['tenant_id', 'quiz_id', 'user_id'],
                        ['status', 'out_status', 'updated_at']
                    );
                    if (count($chunks) > 1) {
                        usleep(5000); // 5ms breathing room for MySQL dirty page flush & CPU relief
                    }
                }
            } catch (\Throwable $dbEx) {
                Log::error("RedisQuizService::collectAndBulkUpsertPendingEnrollments DB failure, requeueing items: " . $dbEx->getMessage());
                try {
                    Redis::pipeline(function ($pipe) use ($rawItems) {
                        foreach ($rawItems as $itemStr) {
                            $pipe->rpush(self::PENDING_ENROLLMENTS_QUEUE, $itemStr);
                        }
                    });
                } catch (\Throwable $requeueEx) {}
                throw $dbEx;
            }

            return count($rows);
        } catch (\Throwable $e) {
            Log::error("RedisQuizService::collectAndBulkUpsertPendingEnrollments error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Bulk persist all Redis gameplay answers and rankings into MySQL database.
     * Uses bulk upsert() in chunks of 1000 to handle 50,000+ players efficiently without sequential queries.
     */
    public static function persistQuizDataToMySQL(int $quizId): array
    {
        if (!self::isAvailable()) {
            return ['success' => false, 'message' => 'Redis is not available'];
        }

        try {
            // Step 1: Drain ALL pending answers queue items first (in chunks of 5000)
            do {
                $drained = self::collectAndBulkUpsertPendingAnswers(5000);
            } while ($drained >= 5000);

            // Step 1b: Drain ALL pending enrollments queue items (in chunks of 5000)
            do {
                $drainedEnrollments = self::collectAndBulkUpsertPendingEnrollments(5000);
            } while ($drainedEnrollments >= 5000);

            $quiz = Quiz::find($quizId);
            if (!$quiz) {
                return ['success' => false, 'message' => 'Quiz not found'];
            }

            $tenantId = $quiz->tenant_id ?? 1;
            $lbKey = self::getLeaderboardKey($quizId);
            $allPlayersWithScores = Redis::zrevrange($lbKey, 0, -1, ['WITHSCORES' => true]);

            if (empty($allPlayersWithScores)) {
                // Check if players set exists
                $playerIds = Redis::smembers(self::getPlayersKey($quizId));
                if (empty($playerIds)) {
                    return ['success' => true, 'message' => 'No in-memory players found to sync', 'synced_players' => 0];
                }
            } else {
                $playerIds = array_map('intval', array_keys($allPlayersWithScores));
            }

            $syncedAnswersCount = 0;
            $syncedWinnersCount = 0;

            // Clean up any existing records with 0 attempted questions for this quiz
            QuizWinner::where('quiz_id', $quizId)
                ->where('tenant_id', $tenantId)
                ->where(function ($q) {
                    $q->where('total_question', '<', 1)
                      ->orWhereNull('total_question');
                })
                ->delete();

            $rankRewards = QuizRankReward::where('quiz_id', $quizId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->get();

            $rank = 1;
            $nowStr = now()->toDateTimeString();
            $playerChunks = array_chunk($playerIds, 500);

            foreach ($playerChunks as $chunk) {
                // Read all answers and metadata for this chunk in 1 Redis pipeline round-trip
                $chunkData = Redis::pipeline(function ($pipe) use ($quizId, $chunk) {
                    foreach ($chunk as $userId) {
                        $pipe->hgetall(self::getUserAnswersKey($quizId, (int) $userId));
                        $pipe->hgetall(self::getUserMetaKey($quizId, (int) $userId));
                    }
                });

                $answersToUpsert = [];
                $winnersToUpsert = [];
                $dataIndex = 0;

                foreach ($chunk as $userId) {
                    $uid = (int) $userId;
                    $rawAnswers = $chunkData[$dataIndex++] ?? [];
                    $meta = $chunkData[$dataIndex++] ?? [];

                    $userTotalQ = 0;
                    $userCorrectQ = 0;
                    $userTotalTime = 0.0;

                    if (!empty($rawAnswers)) {
                        foreach ($rawAnswers as $qIdStr => $ansJson) {
                            $ans = json_decode((string) $ansJson, true);
                            if (!$ans) continue;

                            $qId = (int) ($ans['question_id'] ?? $qIdStr);
                            $isCorrect = (bool) ($ans['is_correct'] ?? false);
                            $tTime = (float) ($ans['total_time'] ?? 0);

                            $userTotalQ++;
                            if ($isCorrect) $userCorrectQ++;
                            $userTotalTime += $tTime;

                            $answersToUpsert[] = [
                                'tenant_id'   => $tenantId,
                                'quiz_id'     => $quizId,
                                'question_id' => $qId,
                                'user_id'     => $uid,
                                'answer'      => $ans['answer'] ?? null,
                                'status'      => $isCorrect ? 1 : 0,
                                'show_time'   => !empty($ans['show_time']) ? $ans['show_time'] : null,
                                'ans_time'    => $ans['ans_time'] ?? '00:00:00',
                                'total_time'  => number_format($tTime, 3, '.', ''),
                                'created_at'  => $ans['created_at'] ?? $nowStr,
                                'updated_at'  => $nowStr,
                            ];
                        }
                    } else {
                        $userTotalQ = (int) ($meta['total_questions'] ?? 0);
                        $userCorrectQ = (int) ($meta['correct_count'] ?? 0);
                        $userTotalTime = (float) ($meta['total_time'] ?? 0);
                    }

                    if ($userTotalQ < 1) {
                        continue;
                    }

                    $userTotalTime = round($userTotalTime, 3);
                    $perAnsAvgTime = $userTotalQ > 0 ? round($userTotalTime / $userTotalQ, 3) : 0;

                    // Calculate reward
                    $rewardVal = (float) ($userCorrectQ * 10);
                    $rewardType = 'point';
                    if ($rankRewards->isNotEmpty()) {
                        $rule = $rankRewards->first(fn($r) => $rank >= $r->start_rank && $rank <= $r->end_rank);
                        if ($rule) {
                            $rewardVal = (float) $rule->reward;
                            $rewardType = $rule->type ?? 'point';
                        }
                    }

                    $winnersToUpsert[] = [
                        'tenant_id'            => $tenantId,
                        'quiz_id'              => $quizId,
                        'user_id'              => $uid,
                        'total_question'       => $userTotalQ,
                        'total_correct_answer' => $userCorrectQ,
                        'total_time'           => $userTotalTime,
                        'per_ans_average_time' => $perAnsAvgTime,
                        'rank'                 => $rank,
                        'reward'               => $rewardVal,
                        'reward_type'          => $rewardType,
                        'reward_detail'        => "Rank #{$rank} Reward",
                        'status'               => 'active',
                        'created_at'           => $nowStr,
                        'updated_at'           => $nowStr,
                    ];

                    $rank++;
                }

                // Bulk upsert answers in chunks of 1000
                if (!empty($answersToUpsert)) {
                    foreach (array_chunk($answersToUpsert, 1000) as $ansBatch) {
                        QuizQuestionUserAnswer::upsert(
                            $ansBatch,
                            ['tenant_id', 'quiz_id', 'user_id', 'question_id'],
                            ['answer', 'status', 'show_time', 'ans_time', 'total_time', 'updated_at']
                        );
                        $syncedAnswersCount += count($ansBatch);
                    }
                }

                // Bulk upsert winners in chunks of 1000
                if (!empty($winnersToUpsert)) {
                    foreach (array_chunk($winnersToUpsert, 1000) as $winBatch) {
                        QuizWinner::upsert(
                            $winBatch,
                            ['tenant_id', 'quiz_id', 'user_id'],
                            ['total_question', 'total_correct_answer', 'total_time', 'per_ans_average_time', 'rank', 'reward', 'reward_type', 'reward_detail', 'status', 'updated_at']
                        );
                        $syncedWinnersCount += count($winBatch);
                    }
                }
            }

            QuizWinner::recalculateRanks((int) $quizId);

            Log::info("RedisQuizService::persistQuizDataToMySQL: Quiz {$quizId} bulk upserted {$syncedAnswersCount} answers, {$syncedWinnersCount} winners.");

            return [
                'success'        => true,
                'quiz_id'        => $quizId,
                'synced_answers' => $syncedAnswersCount,
                'synced_winners' => $syncedWinnersCount,
            ];
        } catch (\Throwable $e) {
            Log::error("RedisQuizService::persistQuizDataToMySQL failed for quiz {$quizId}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Clean up unused Redis data for a user immediately after reconciliation completes.
     * Deletes the user's answers hash and frees memory instantly since all answers
     * are now permanently written to MySQL database.
     */
    public static function cleanupUserReconciledData(int $quizId, int $userId): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        try {
            $answersKey = self::getUserAnswersKey($quizId, $userId);
            Redis::del($answersKey);

            // Set shorter expiration (5 min) on user meta key so leaderboard has it briefly, but it auto-expires soon
            $metaKey = self::getUserMetaKey($quizId, $userId);
            Redis::expire($metaKey, 300);

            return true;
        } catch (\Throwable $e) {
            Log::warning("RedisQuizService::cleanupUserReconciledData failed for quiz {$quizId}, user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear all Redis keys for a specific quiz after lifecycle completion.
     */
    public static function clearQuizData(int $quizId): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        try {
            $players = Redis::smembers(self::getPlayersKey($quizId));
            if (!empty($players)) {
                $playerChunks = array_chunk($players, 500);
                foreach ($playerChunks as $pChunk) {
                    Redis::pipeline(function ($pipe) use ($quizId, $pChunk) {
                        foreach ($pChunk as $uid) {
                            $pipe->del(self::getUserAnswersKey($quizId, (int) $uid));
                            $pipe->del(self::getUserMetaKey($quizId, (int) $uid));
                        }
                    });
                }
            }

            Redis::del(self::getPlayersKey($quizId));
            Redis::del(self::getQuestionsKey($quizId));
            Redis::del(self::getSwitchQuestionsKey($quizId));
            Redis::del(self::getLeaderboardKey($quizId));

            return true;
        } catch (\Throwable $e) {
            Log::error("RedisQuizService::clearQuizData failed for quiz {$quizId}: " . $e->getMessage());
            return false;
        }
    }
}
