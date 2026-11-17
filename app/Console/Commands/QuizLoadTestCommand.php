<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizUser;
use App\Models\QuizQuestionUserAnswer;
use App\Models\QuizWinner;
use App\Models\QuizRankReward;
use App\Models\QuizStatus;
use App\Models\User;
use App\Services\RedisQuizService;
use App\Services\FffQuizService;
use App\Interfaces\api\QuizRepositoryInterface;
use App\Interfaces\api\QuizWinnerRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class QuizLoadTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quiz:load-test 
        {--vus=50 : Number of virtual users} 
        {--duration=60 : Duration of test in seconds} 
        {--quiz_id= : Specific quiz ID, or auto_normal, or auto_fff} 
        {--stages=join,waiting,start_counter,questions,sounds,answers,review_counter,answersheet,update_play_data,leaderboard : Comma-separated list of test scenarios}
        {--output= : Path to save JSON report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate concurrent user quiz lifecycle: joining, waiting, start counter, questions, sounds, answers, review counter, answer sheet, update-play-data, and leaderboard for Normal & FFF quizzes';

    /**
     * Helper to read current CPU load percentage (Windows / Linux compatible)
     */
    protected function getCpuUsage(): float
    {
        try {
            if (stristr(PHP_OS, 'WIN')) {
                $cmd = 'powershell -NoProfile -Command "Get-CimInstance Win32_Processor | Measure-Object -Property LoadPercentage -Average | Select-Object -ExpandProperty Average"';
                $output = @shell_exec($cmd);
                if ($output !== null && is_numeric(trim($output))) {
                    return (float) trim($output);
                }
            } elseif (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                if (isset($load[0])) {
                    return round((float) $load[0] * 10, 2);
                }
            }
        } catch (\Throwable $e) {}
        return 0.0;
    }

    /**
     * Helper to read current RAM usage percentage (Windows / Linux compatible)
     */
    protected function getMemoryUsage(): float
    {
        try {
            if (stristr(PHP_OS, 'WIN')) {
                $cmd = 'powershell -NoProfile -Command "Get-CimInstance Win32_OperatingSystem | ForEach-Object { [math]::Round((( $_.TotalVisibleMemorySize - $_.FreePhysicalMemory) / $_.TotalVisibleMemorySize) * 100, 1) }"';
                $output = @shell_exec($cmd);
                if ($output !== null && is_numeric(trim($output))) {
                    return (float) trim($output);
                }
            } else {
                $free = @shell_exec('free');
                if ($free) {
                    $free = (string)trim($free);
                    $free_arr = explode("\n", $free);
                    if (isset($free_arr[1])) {
                        $mem = explode(" ", preg_replace('/\s+/', ' ', $free_arr[1]));
                        if (isset($mem[1], $mem[2]) && (int)$mem[1] > 0) {
                            return round(((int)$mem[2] / (int)$mem[1]) * 100, 1);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {}
        return 0.0;
    }

    /**
     * Calculate percentile from sorted numeric array
     */
    protected function getPercentile(array $sortedValues, float $percentile): float
    {
        $count = count($sortedValues);
        if ($count === 0) return 0.0;
        $index = (int) ceil(($percentile / 100) * $count) - 1;
        return round($sortedValues[max(0, min($index, $count - 1))], 2);
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        ini_set('memory_limit', '1024M');
        DB::disableQueryLog();

        $vus = (int) ($this->option('vus') ?: 50);
        $duration = (int) ($this->option('duration') ?: 60);
        $outputPath = $this->option('output') ?: storage_path('app/load-test-report.json');
        
        $stagesOption = strtolower($this->option('stages') ?: 'join,waiting,start_counter,questions,sounds,answers,review_counter,answersheet,update_play_data,leaderboard');
        $stages = array_filter(array_map('trim', explode(',', $stagesOption)));

        $testJoin           = in_array('join', $stages);
        $testWaiting        = in_array('waiting', $stages);
        $testStartCounter   = in_array('start_counter', $stages);
        $testQuestions      = in_array('questions', $stages);
        $testSounds         = in_array('sounds', $stages);
        $testAnswers        = in_array('answers', $stages);
        $testReviewCounter  = in_array('review_counter', $stages);
        $testAnswerSheet    = in_array('answersheet', $stages);
        $testUpdatePlayData = in_array('update_play_data', $stages);
        $testLeaderboard    = in_array('leaderboard', $stages);

        $this->info("==================================================");
        $this->info("   FSRQuiz Multi-Module Virtual User Load Test   ");
        $this->info("==================================================");
        $this->line("Concurrent VUs     : {$vus}");
        $this->line("Target Duration    : {$duration}s");
        $this->line("Selected Scenarios : " . implode(', ', array_map('strtoupper', $stages)));
        $this->line("Output File        : {$outputPath}");
        $this->newLine();

        $repo = app(QuizRepositoryInterface::class);
        $fffService = app(FffQuizService::class);
        $winnerRepo = app(QuizWinnerRepositoryInterface::class);

        // 1. Resolve Target Quiz
        $rawQuizId = $this->option('quiz_id');
        $quiz = null;

        $isTemporaryQuiz = false;

        if ($rawQuizId === 'auto_fff') {
            $quiz = Quiz::where('visibility', 'fastest_finger_first')->whereHas('questions')->latest('id')->first()
                ?: Quiz::where('visibility', 'fastest_finger_first')->latest('id')->first();

            // If no FFF quiz exists in database, use any template quiz and clone as FFF
            if (!$quiz) {
                $baseQuiz = Quiz::whereHas('questions')->latest('id')->first()
                    ?: Quiz::withTrashed()->whereHas('questions')->latest('id')->first()
                    ?: Quiz::withTrashed()->latest('id')->first();
                if ($baseQuiz) {
                    $quiz = $baseQuiz->replicate();
                    $quiz->title = 'Simulated Load Test FFF Quiz';
                    $quiz->visibility = 'fastest_finger_first';
                    $quiz->scheduled_at = now()->addMinutes(25);
                    $quiz->complete_status = 'pending';
                    $quiz->deleted_at = null;
                    $quiz->save();

                    foreach (QuizQuestion::where('quiz_id', $baseQuiz->id)->get() as $tq) {
                        $newQ = $tq->replicate();
                        $newQ->quiz_id = $quiz->id;
                        $newQ->save();
                    }

                    foreach (QuizRankReward::where('quiz_id', $baseQuiz->id)->get() as $rw) {
                        $newRw = $rw->replicate();
                        $newRw->quiz_id = $quiz->id;
                        $newRw->save();
                    }
                    $isTemporaryQuiz = true;
                }
            }
        } elseif ($rawQuizId === 'auto_normal') {
            $quiz = Quiz::where('visibility', '!=', 'fastest_finger_first')->whereHas('questions')->latest('id')->first()
                ?: Quiz::where('visibility', '!=', 'fastest_finger_first')->latest('id')->first()
                ?: Quiz::withTrashed()->where('visibility', '!=', 'fastest_finger_first')->whereHas('questions')->latest('id')->first()
                ?: Quiz::withTrashed()->where('visibility', '!=', 'fastest_finger_first')->latest('id')->first();
        } elseif (!empty($rawQuizId) && is_numeric($rawQuizId)) {
            $quiz = Quiz::withTrashed()->find((int) $rawQuizId);
        }

        if (!$quiz) {
            $quiz = Quiz::whereHas('questions')->latest('id')->first()
                ?: Quiz::withTrashed()->whereHas('questions')->latest('id')->first()
                ?: Quiz::latest('id')->first()
                ?: Quiz::withTrashed()->latest('id')->first();
        }

        if (!$quiz) {
            $this->error("No active quiz with questions found for load testing.");
            return Command::FAILURE;
        }

        $isFff = ($quiz->visibility === 'fastest_finger_first');
        $templateQuestions = QuizQuestion::where('quiz_id', $quiz->id)->get();
        if ($templateQuestions->isEmpty()) {
            $this->error("Selected quiz (ID: {$quiz->id}) has no questions.");
            return Command::FAILURE;
        }

        // If target quiz has already finished or expired, create dedicated temporary test quiz
        $testQuiz = $quiz;

        $isExpired = false;
        if ($quiz->trashed() || $quiz->complete_status === 'completed') {
            $isExpired = true;
        } elseif ($quiz->scheduled_at && Carbon::parse($quiz->scheduled_at)->isPast()) {
            $isExpired = true;
        }

        if (($testJoin || $testAnswers || $testWaiting) && $isExpired) {
            $this->warn("Note: Selected Quiz #{$quiz->id} schedule time is past. Creating temporary active test quiz for realistic benchmarking...");
            try {
                $testQuiz = Quiz::create([
                    'title'               => 'Simulated Load Test ' . ($isFff ? 'FFF' : 'Normal') . ' Quiz',
                    'tenant_id'           => $quiz->tenant_id ?? 1,
                    'visibility'          => $isFff ? 'fastest_finger_first' : 'public',
                    'scheduled_at'        => now()->addMinutes(25),
                    'complete_status'     => 'pending',
                    'no_of_players'       => max(100000, $vus * 10),
                    'total_reward_amount' => $quiz->total_reward_amount ?: 500,
                    'question_limit'      => $quiz->question_limit ?: 10,
                ]);

                // Copy questions to temporary quiz
                foreach ($templateQuestions as $tq) {
                    QuizQuestion::create([
                        'tenant_id'            => $quiz->tenant_id ?? 1,
                        'quiz_id'              => $testQuiz->id,
                        'question_id'          => $tq->question_id ?: $tq->id,
                        'order'                => $tq->order,
                        'question_text'        => $tq->question_text,
                        'options'              => $tq->options,
                        'correct_answer'       => $tq->correct_answer,
                        'hindi_question'       => $tq->hindi_question,
                        'hindi_options'        => $tq->hindi_options,
                        'hindi_correct_answer' => $tq->hindi_correct_answer,
                        'type'                 => $tq->type ?: 'mcq',
                        'status'               => 'published',
                    ]);
                }

                // Copy rank rewards if any so updatePlayData can calculate rewards
                $rewards = QuizRankReward::where('quiz_id', $quiz->id)->get();
                foreach ($rewards as $rw) {
                    QuizRankReward::create([
                        'tenant_id'   => $testQuiz->tenant_id ?? 1,
                        'quiz_id'     => $testQuiz->id,
                        'start_rank'  => $rw->start_rank,
                        'end_rank'    => $rw->end_rank,
                        'reward'      => $rw->reward,
                        'reward_type' => $rw->reward_type,
                    ]);
                }

                $isTemporaryQuiz = true;
                $this->info("Created temporary active Quiz #{$testQuiz->id} for load test.");
            } catch (\Throwable $e) {
                $this->warn("Could not create temporary quiz ({$e->getMessage()}), testing directly with Quiz #{$quiz->id}.");
                $testQuiz = $quiz;
            }
        }

        $activeQuizId = $testQuiz->id;
        $questions = QuizQuestion::where('quiz_id', $activeQuizId)->get();
        $questionList = $questions->values();
        $qCount = $questionList->count();

        $this->info("Target Quiz ID       : {$activeQuizId} ({$testQuiz->title})");
        $this->info("Quiz Mode            : " . ($isFff ? '⚡ FASTEST FINGER FIRST (FFF)' : '🎯 NORMAL QUIZ'));
        $this->info("Questions in Quiz    : {$qCount}");
        $this->info("Redis Engine Status  : " . (RedisQuizService::isAvailable() ? 'CONNECTED' : 'UNAVAILABLE (MySQL direct)'));
        $this->newLine();

        // 2. Virtual Users Pool
        $existingUsers = User::limit($vus)->pluck('id')->toArray();
        $users = $existingUsers;
        if (count($users) < $vus) {
            for ($i = count($users) + 1; $i <= $vus; $i++) {
                $users[] = 900000 + $i;
            }
        }

        // Metrics Tracking Containers for all 10 modules
        $joinMetrics           = ['total' => 0, 'success' => 0, 'failed' => 0, 'latencies' => []];
        $waitingMetrics        = ['total' => 0, 'success' => 0, 'failed' => 0, 'latencies' => []];
        $startCounterMetrics   = ['total' => 0, 'success' => 0, 'failed' => 0, 'latencies' => []];
        $questionsMetrics      = ['total' => 0, 'success' => 0, 'failed' => 0, 'latencies' => []];
        $soundsMetrics         = ['total' => 0, 'success' => 0, 'failed' => 0, 'latencies' => []];
        $answersMetrics        = ['total' => 0, 'success' => 0, 'failed' => 0, 'latencies' => []];
        $reviewCounterMetrics  = ['total' => 0, 'success' => 0, 'failed' => 0, 'latencies' => []];
        $answerSheetMetrics    = ['total' => 0, 'success' => 0, 'failed' => 0, 'latencies' => []];
        $updatePlayDataMetrics = ['total' => 0, 'success' => 0, 'failed' => 0, 'latencies' => []];
        $leaderboardMetrics    = ['total' => 0, 'success' => 0, 'failed' => 0, 'latencies' => []];

        $allLatencies = [];
        $cpuSamples = [];
        $ramSamples = [];

        // Sample initial CPU & RAM
        $initialCpu = $this->getCpuUsage();
        $initialRam = $this->getMemoryUsage();
        if ($initialCpu > 0) $cpuSamples[] = $initialCpu;
        if ($initialRam > 0) $ramSamples[] = $initialRam;

        $startTime = microtime(true);
        $endTime = $startTime + $duration;

        $this->info("Starting load test loop across enabled modules...");
        $bar = $this->output->createProgressBar($duration);
        $bar->start();

        $lastTick = (int) $startTime;
        $lastMetricSampleTime = microtime(true);
        $lastUpdatePlayDataTime = 0.0;
        $totalIteration = 0;

        $recordLatency = function (array &$metrics, float $lat) use (&$allLatencies) {
            if (count($metrics['latencies']) < 5000) {
                $metrics['latencies'][] = $lat;
            }
            if (count($allLatencies) < 15000) {
                $allLatencies[] = $lat;
            }
        };

        try {
            while (microtime(true) < $endTime) {
                $currentNow = microtime(true);
                $elapsedSec = (int) ($currentNow - $startTime);

                if ((int) $currentNow > $lastTick) {
                    $bar->setProgress(min($elapsedSec, $duration));
                    $lastTick = (int) $currentNow;
                }

                // Sample CPU & RAM every 4 seconds
                if (($currentNow - $lastMetricSampleTime) >= 4.0) {
                    $c = $this->getCpuUsage();
                    $m = $this->getMemoryUsage();
                    if ($c > 0) $cpuSamples[] = $c;
                    if ($m > 0) $ramSamples[] = $m;
                    $lastMetricSampleTime = $currentNow;
                }

                // Pick a slice of VUs for this batch iteration (balanced concurrency window for large VU pools)
                $batchSize = min(count($users), max(4, min(50, (int) ceil($vus / 20))));
                $batchVus = array_rand($users, $batchSize);
                if (!is_array($batchVus)) {
                    $batchVus = [$batchVus];
                }

                foreach ($batchVus as $uIdx) {
                    $userId = $users[$uIdx];
                    $mockUser = (object) [
                        'id' => $userId,
                        'tenant_id' => $testQuiz->tenant_id ?? 1,
                        'available_quiz_limit' => 9999,
                    ];

                    // ── MODULE 1: QUIZ JOINING LOAD ──────────────────
                    if ($testJoin) {
                        $joinStart = microtime(true);
                        try {
                            $joinRes = $repo->joinSubmitData($mockUser, $activeQuizId);
                            $joinLatency = (microtime(true) - $joinStart) * 1000;
                            $joinMetrics['total']++;
                            $recordLatency($joinMetrics, $joinLatency);

                            if (!empty($joinRes['success'])) {
                                $joinMetrics['success']++;
                            } else {
                                $joinMetrics['failed']++;
                            }
                        } catch (\Throwable $e) {
                            $joinLatency = (microtime(true) - $joinStart) * 1000;
                            $joinMetrics['total']++;
                            $joinMetrics['failed']++;
                            $recordLatency($joinMetrics, $joinLatency);
                        }
                    }

                    // ── MODULE 2: WAITING PAGE & STATUS ──────────────
                    if ($testWaiting) {
                        $waitingStart = microtime(true);
                        try {
                            $waitingRes = $repo->getStatusFromSessionData($activeQuizId);
                            $waitingLatency = (microtime(true) - $waitingStart) * 1000;
                            $waitingMetrics['total']++;
                            $waitingMetrics['success']++;
                            $recordLatency($waitingMetrics, $waitingLatency);
                        } catch (\Throwable $e) {
                            $waitingLatency = (microtime(true) - $waitingStart) * 1000;
                            $waitingMetrics['total']++;
                            $waitingMetrics['failed']++;
                            $recordLatency($waitingMetrics, $waitingLatency);
                        }
                    }

                    // ── MODULE 3: START COUNTER ──────────────────────
                    if ($testStartCounter) {
                        $scStart = microtime(true);
                        try {
                            $schedTime = !empty($testQuiz->scheduled_at) ? Carbon::parse($testQuiz->scheduled_at) : now();
                            $counterMs = Cache::remember("quiz_{$activeQuizId}_counter_start_ms", 10, function () use ($schedTime) {
                                return (int) ($schedTime->getTimestamp() * 1000);
                            });
                            $scLatency = (microtime(true) - $scStart) * 1000;
                            $startCounterMetrics['total']++;
                            $startCounterMetrics['success']++;
                            $recordLatency($startCounterMetrics, $scLatency);
                        } catch (\Throwable $e) {
                            $scLatency = (microtime(true) - $scStart) * 1000;
                            $startCounterMetrics['total']++;
                            $startCounterMetrics['failed']++;
                            $recordLatency($startCounterMetrics, $scLatency);
                        }
                    }

                    // ── MODULE 4: QUESTIONS LOAD ─────────────────────
                    if ($testQuestions) {
                        $qStart = microtime(true);
                        try {
                            if ($isFff) {
                                $qRes = $fffService->getFffQuestionsFromSession($activeQuizId, $mockUser);
                            } else {
                                $qRes = $repo->preloadQuizQuestionsToSession($activeQuizId);
                            }
                            $qLatency = (microtime(true) - $qStart) * 1000;
                            $questionsMetrics['total']++;
                            $recordLatency($questionsMetrics, $qLatency);

                            if (!empty($qRes)) {
                                $questionsMetrics['success']++;
                            } else {
                                $questionsMetrics['failed']++;
                            }
                        } catch (\Throwable $e) {
                            $qLatency = (microtime(true) - $qStart) * 1000;
                            $questionsMetrics['total']++;
                            $questionsMetrics['failed']++;
                            $recordLatency($questionsMetrics, $qLatency);
                        }
                    }

                    // ── MODULE 5: SOUND LOAD ─────────────────────────
                    if ($testSounds) {
                        $soundStart = microtime(true);
                        try {
                            $soundRes = $repo->getSoundsData();
                            $soundLatency = (microtime(true) - $soundStart) * 1000;
                            $soundsMetrics['total']++;
                            $recordLatency($soundsMetrics, $soundLatency);

                            if (!empty($soundRes['success'])) {
                                $soundsMetrics['success']++;
                            } else {
                                $soundsMetrics['failed']++;
                            }
                        } catch (\Throwable $e) {
                            $soundLatency = (microtime(true) - $soundStart) * 1000;
                            $soundsMetrics['total']++;
                            $soundsMetrics['failed']++;
                            $recordLatency($soundsMetrics, $soundLatency);
                        }
                    }

                    // ── MODULE 6: ANSWER SUBMISSIONS LOAD ────────────
                    if ($testAnswers && $qCount > 0) {
                        $qIdx = $totalIteration % $qCount;
                        $targetQuestion = $questionList[$qIdx];
                        $selectedOption = 'option_' . rand(1, 4);

                        $ansStart = microtime(true);
                        try {
                            if (RedisQuizService::isAvailable()) {
                                RedisQuizService::enqueuePendingAnswer([
                                    'quiz_id' => $activeQuizId,
                                    'quiz_question_id' => $targetQuestion->id,
                                    'user_id' => $userId,
                                    'answer' => $selectedOption,
                                    'is_correct' => rand(0, 1),
                                    'created_at' => now()->toDateTimeString(),
                                    'updated_at' => now()->toDateTimeString(),
                                ]);
                            } else {
                                QuizQuestionUserAnswer::updateOrCreate(
                                    [
                                        'quiz_id' => $activeQuizId,
                                        'question_id' => $targetQuestion->id,
                                        'user_id' => $userId,
                                    ],
                                    [
                                        'tenant_id' => $testQuiz->tenant_id ?? 1,
                                        'answer' => $selectedOption,
                                        'status' => (bool) rand(0, 1),
                                        'ans_time' => now()->format('H:i:s.v'),
                                        'total_time' => round(rand(100, 3000) / 1000, 3),
                                    ]
                                );
                            }

                            $ansLatency = (microtime(true) - $ansStart) * 1000;
                            $answersMetrics['total']++;
                            $answersMetrics['success']++;
                            $recordLatency($answersMetrics, $ansLatency);
                        } catch (\Throwable $e) {
                            $ansLatency = (microtime(true) - $ansStart) * 1000;
                            $answersMetrics['total']++;
                            $answersMetrics['failed']++;
                            $recordLatency($answersMetrics, $ansLatency);
                        }
                    }

                    // ── MODULE 7: REVIEW COUNTER ─────────────────────
                    if ($testReviewCounter) {
                        $rcStart = microtime(true);
                        try {
                            $schedTime = !empty($testQuiz->scheduled_at) ? Carbon::parse($testQuiz->scheduled_at) : now();
                            $reviewStartMs = ($schedTime->getTimestamp() * 1000) + 10000 + ($qCount * 17000);
                            $reviewRemaining = max(0, ($reviewStartMs + 10000) - (int)(microtime(true) * 1000));
                            $rcLatency = (microtime(true) - $rcStart) * 1000;
                            $reviewCounterMetrics['total']++;
                            $reviewCounterMetrics['success']++;
                            $recordLatency($reviewCounterMetrics, $rcLatency);
                        } catch (\Throwable $e) {
                            $rcLatency = (microtime(true) - $rcStart) * 1000;
                            $reviewCounterMetrics['total']++;
                            $reviewCounterMetrics['failed']++;
                            $recordLatency($reviewCounterMetrics, $rcLatency);
                        }
                    }

                    // ── MODULE 8: ANSWER SHEET PAGE ──────────────────
                    if ($testAnswerSheet) {
                        $asStart = microtime(true);
                        try {
                            if ($isFff) {
                                $asRes = $fffService->getFffAnswerSheetData($mockUser, $activeQuizId);
                            } else {
                                $asRes = $repo->getAnswerSheetDataForApp($mockUser, $activeQuizId);
                            }
                            $asLatency = (microtime(true) - $asStart) * 1000;
                            $answerSheetMetrics['total']++;
                            $recordLatency($answerSheetMetrics, $asLatency);

                            if (!empty($asRes)) {
                                $answerSheetMetrics['success']++;
                            } else {
                                $answerSheetMetrics['failed']++;
                            }
                        } catch (\Throwable $e) {
                            $asLatency = (microtime(true) - $asStart) * 1000;
                            $answerSheetMetrics['total']++;
                            $answerSheetMetrics['failed']++;
                            $recordLatency($answerSheetMetrics, $asLatency);
                        }
                    }

                    // ── MODULE 10: LEADERBOARD PAGE ──────────────────
                    if ($testLeaderboard) {
                        $lbStart = microtime(true);
                        try {
                            if ($isFff) {
                                $lbRes = $fffService->getFffLeaderboardData($mockUser, $activeQuizId);
                            } else {
                                $lbRes = $repo->getLeaderboardDataForApp($mockUser, $activeQuizId);
                            }
                            $lbLatency = (microtime(true) - $lbStart) * 1000;
                            $leaderboardMetrics['total']++;
                            $recordLatency($leaderboardMetrics, $lbLatency);

                            if (!empty($lbRes)) {
                                $leaderboardMetrics['success']++;
                            } else {
                                $leaderboardMetrics['failed']++;
                            }
                        } catch (\Throwable $e) {
                            $lbLatency = (microtime(true) - $lbStart) * 1000;
                            $leaderboardMetrics['total']++;
                            $leaderboardMetrics['failed']++;
                            $recordLatency($leaderboardMetrics, $lbLatency);
                        }
                    }

                    $totalIteration++;
                }

                // ── MODULE 9: UPDATE PLAY DATA (Admin Trigger) ────────
                // Triggered periodically (every 25s or at end) to simulate SuperAdmin clicking /quizzes/{id}/update-play-data
                $canRunAdminUpdate = $testUpdatePlayData && (($currentNow - $lastUpdatePlayDataTime) >= 25.0 || $lastUpdatePlayDataTime === 0.0);
                if ($canRunAdminUpdate) {
                    $lastUpdatePlayDataTime = $currentNow;
                    $updStart = microtime(true);
                    try {
                        if (!in_array($testQuiz->complete_status, ['processing', 'completed'])) {
                            $testQuiz->update(['complete_status' => 'processing']);
                            $testQuiz->refresh();
                        }
                        $updRes = $winnerRepo->quizUpdatePlayData($testQuiz, true);
                        if ($testJoin) {
                            Quiz::where('id', $testQuiz->id)->update(['complete_status' => 'pending']);
                            $testQuiz->refresh();
                        }
                        $updLatency = (microtime(true) - $updStart) * 1000;
                        $updatePlayDataMetrics['total']++;
                        $recordLatency($updatePlayDataMetrics, $updLatency);

                        if (!empty($updRes['success'])) {
                            $updatePlayDataMetrics['success']++;
                        } else {
                            $updatePlayDataMetrics['failed']++;
                        }
                    } catch (\Throwable $e) {
                        $updLatency = (microtime(true) - $updStart) * 1000;
                        $updatePlayDataMetrics['total']++;
                        $updatePlayDataMetrics['failed']++;
                        $recordLatency($updatePlayDataMetrics, $updLatency);
                    }
                }

                // Micro-sleep to pace VU concurrency (simulating realistic client think time)
                usleep(12000);
            }
        } finally {
            $bar->finish();
            $this->newLine(2);

            // Clean up temporary test quiz & mock enrollments
            if ($isTemporaryQuiz) {
                try {
                    QuizUser::where('quiz_id', $activeQuizId)->forceDelete();
                    QuizQuestionUserAnswer::where('quiz_id', $activeQuizId)->forceDelete();
                    QuizWinner::where('quiz_id', $activeQuizId)->forceDelete();
                    QuizQuestion::where('quiz_id', $activeQuizId)->forceDelete();
                    QuizRankReward::where('quiz_id', $activeQuizId)->forceDelete();
                    QuizStatus::where('quiz_id', $activeQuizId)->forceDelete();
                    $testQuiz->forceDelete();
                } catch (\Throwable $e) {}
            } else {
                // Remove simulated mock users from target quiz
                try {
                    QuizUser::where('quiz_id', $activeQuizId)->where('user_id', '>=', 900000)->forceDelete();
                    QuizQuestionUserAnswer::where('quiz_id', $activeQuizId)->where('user_id', '>=', 900000)->forceDelete();
                } catch (\Throwable $e) {}
            }
            DB::enableQueryLog();
            gc_collect_cycles();
        }

        // Final CPU & RAM sample
        $finalCpu = $this->getCpuUsage();
        $finalRam = $this->getMemoryUsage();
        if ($finalCpu > 0) $cpuSamples[] = $finalCpu;
        if ($finalRam > 0) $ramSamples[] = $finalRam;

        $actualDuration = microtime(true) - $startTime;
        $totalCombinedRequests = $joinMetrics['total'] 
            + $waitingMetrics['total'] 
            + $startCounterMetrics['total'] 
            + $questionsMetrics['total'] 
            + $soundsMetrics['total'] 
            + $answersMetrics['total'] 
            + $reviewCounterMetrics['total'] 
            + $answerSheetMetrics['total'] 
            + $updatePlayDataMetrics['total'] 
            + $leaderboardMetrics['total'];

        $combinedRps = $actualDuration > 0 ? round($totalCombinedRequests / $actualDuration, 2) : 0;

        // Helper closure for module stats
        $calcStats = function (array $metrics) use ($actualDuration) {
            $total = $metrics['total'];
            $success = $metrics['success'];
            $failed = $metrics['failed'];
            $l = $metrics['latencies'];
            sort($l);
            $avg = count($l) ? round(array_sum($l) / count($l), 2) : 0;
            $min = count($l) ? round(min($l), 2) : 0;
            $max = count($l) ? round(max($l), 2) : 0;
            $p95 = $this->getPercentile($l, 95);
            $rps = $actualDuration > 0 ? round($total / $actualDuration, 2) : 0;
            $rate = $total > 0 ? round(($success / $total) * 100, 1) : 0;

            return [
                'total' => $total,
                'success' => $success,
                'failed' => $failed,
                'success_rate' => "{$rate}%",
                'rps' => $rps,
                'avg_ms' => $avg,
                'p95_ms' => $p95,
                'min_ms' => $min,
                'max_ms' => $max,
            ];
        };

        $joinStats           = $calcStats($joinMetrics);
        $waitingStats        = $calcStats($waitingMetrics);
        $startCounterStats   = $calcStats($startCounterMetrics);
        $questionsStats      = $calcStats($questionsMetrics);
        $soundsStats         = $calcStats($soundsMetrics);
        $answersStats        = $calcStats($answersMetrics);
        $reviewCounterStats  = $calcStats($reviewCounterMetrics);
        $answerSheetStats    = $calcStats($answerSheetMetrics);
        $updatePlayDataStats = $calcStats($updatePlayDataMetrics);
        $leaderboardStats    = $calcStats($leaderboardMetrics);

        sort($allLatencies);
        $globalAvgLatency = count($allLatencies) ? round(array_sum($allLatencies) / count($allLatencies), 2) : 0;
        $globalP95Latency = $this->getPercentile($allLatencies, 95);
        $globalP99Latency = $this->getPercentile($allLatencies, 99);

        $avgCpu = count($cpuSamples) ? round(array_sum($cpuSamples) / count($cpuSamples), 1) : $finalCpu;
        $maxCpu = count($cpuSamples) ? max($cpuSamples) : $finalCpu;
        $avgRam = count($ramSamples) ? round(array_sum($ramSamples) / count($ramSamples), 1) : $finalRam;
        $maxRam = count($ramSamples) ? max($ramSamples) : $finalRam;

        // Print Comprehensive Summary Table
        $this->info("==================================================================");
        $this->info("               LOAD TEST EXECUTION SUMMARY REPORT                 ");
        $this->info("==================================================================");
        $this->line("Target Quiz          : #{$activeQuizId} ({$quiz->title})");
        $this->line("Quiz Architecture    : " . ($isFff ? 'Fastest Finger First (FFF)' : 'Normal Multi-Question Quiz'));
        $this->line("Simulated VUs        : {$vus} concurrent users");
        $this->line("Actual Test Duration : " . round($actualDuration, 2) . "s");
        $this->line("Total Requests       : {$totalCombinedRequests}");
        $this->line("Global Throughput    : {$combinedRps} req/s");
        $this->line("Global Latencies     : Avg: {$globalAvgLatency}ms | P95: {$globalP95Latency}ms | P99: {$globalP99Latency}ms");
        $this->newLine();

        $this->info("------------------- MODULE-WISE PERFORMANCE ----------------------");
        $headers = ['Module Scenario', 'Requests', 'Success', 'Failed', 'Throughput', 'Avg Latency', 'P95 Latency'];
        $tableRows = [];

        if ($testJoin) {
            $tableRows[] = [
                '1. Quiz Joining',
                $joinStats['total'],
                $joinStats['success'] . " ({$joinStats['success_rate']})",
                $joinStats['failed'],
                "{$joinStats['rps']} joins/s",
                "{$joinStats['avg_ms']} ms",
                "{$joinStats['p95_ms']} ms"
            ];
        }

        if ($testWaiting) {
            $tableRows[] = [
                '2. Waiting Page & Status',
                $waitingStats['total'],
                $waitingStats['success'] . " ({$waitingStats['success_rate']})",
                $waitingStats['failed'],
                "{$waitingStats['rps']} req/s",
                "{$waitingStats['avg_ms']} ms",
                "{$waitingStats['p95_ms']} ms"
            ];
        }

        if ($testStartCounter) {
            $tableRows[] = [
                '3. Start Counter (10s)',
                $startCounterStats['total'],
                $startCounterStats['success'] . " ({$startCounterStats['success_rate']})",
                $startCounterStats['failed'],
                "{$startCounterStats['rps']} req/s",
                "{$startCounterStats['avg_ms']} ms",
                "{$startCounterStats['p95_ms']} ms"
            ];
        }

        if ($testQuestions) {
            $tableRows[] = [
                '4. Questions Load',
                $questionsStats['total'],
                $questionsStats['success'] . " ({$questionsStats['success_rate']})",
                $questionsStats['failed'],
                "{$questionsStats['rps']} req/s",
                "{$questionsStats['avg_ms']} ms",
                "{$questionsStats['p95_ms']} ms"
            ];
        }

        if ($testSounds) {
            $tableRows[] = [
                '5. Sound Load',
                $soundsStats['total'],
                $soundsStats['success'] . " ({$soundsStats['success_rate']})",
                $soundsStats['failed'],
                "{$soundsStats['rps']} req/s",
                "{$soundsStats['avg_ms']} ms",
                "{$soundsStats['p95_ms']} ms"
            ];
        }

        if ($testAnswers) {
            $tableRows[] = [
                '6. Answer Submissions',
                $answersStats['total'],
                $answersStats['success'] . " ({$answersStats['success_rate']})",
                $answersStats['failed'],
                "{$answersStats['rps']} answers/s",
                "{$answersStats['avg_ms']} ms",
                "{$answersStats['p95_ms']} ms"
            ];
        }

        if ($testReviewCounter) {
            $tableRows[] = [
                '7. Review Counter (10s)',
                $reviewCounterStats['total'],
                $reviewCounterStats['success'] . " ({$reviewCounterStats['success_rate']})",
                $reviewCounterStats['failed'],
                "{$reviewCounterStats['rps']} req/s",
                "{$reviewCounterStats['avg_ms']} ms",
                "{$reviewCounterStats['p95_ms']} ms"
            ];
        }

        if ($testAnswerSheet) {
            $tableRows[] = [
                '8. Answer Sheet Page',
                $answerSheetStats['total'],
                $answerSheetStats['success'] . " ({$answerSheetStats['success_rate']})",
                $answerSheetStats['failed'],
                "{$answerSheetStats['rps']} req/s",
                "{$answerSheetStats['avg_ms']} ms",
                "{$answerSheetStats['p95_ms']} ms"
            ];
        }

        if ($testUpdatePlayData) {
            $tableRows[] = [
                '9. Update Play Data (Admin)',
                $updatePlayDataStats['total'],
                $updatePlayDataStats['success'] . " ({$updatePlayDataStats['success_rate']})",
                $updatePlayDataStats['failed'],
                "{$updatePlayDataStats['rps']} hits/s",
                "{$updatePlayDataStats['avg_ms']} ms",
                "{$updatePlayDataStats['p95_ms']} ms"
            ];
        }

        if ($testLeaderboard) {
            $tableRows[] = [
                '10. Leaderboard Page',
                $leaderboardStats['total'],
                $leaderboardStats['success'] . " ({$leaderboardStats['success_rate']})",
                $leaderboardStats['failed'],
                "{$leaderboardStats['rps']} req/s",
                "{$leaderboardStats['avg_ms']} ms",
                "{$leaderboardStats['p95_ms']} ms"
            ];
        }

        $this->table($headers, $tableRows);
        $this->newLine();

        $this->info("---------------------- SERVER UTILIZATION ------------------------");
        $this->line("Engine Architecture  : " . (RedisQuizService::isAvailable() ? 'Redis In-Memory Engine (Optimal)' : 'Direct MySQL Engine'));
        $this->line("Server CPU Load      : Avg: {$avgCpu}% | Peak: {$maxCpu}%");
        $this->line("Server Memory (RAM)  : Avg: {$avgRam}% | Peak: {$maxRam}%");
        $this->info("==================================================================");

        // Prepare JSON report
        $reportData = [
            'timestamp' => now()->toIso8601String(),
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'visibility' => $quiz->visibility,
                'is_fff' => $isFff,
                'questions_count' => $qCount,
            ],
            'virtual_users' => $vus,
            'target_duration_seconds' => $duration,
            'actual_duration_seconds' => round($actualDuration, 2),
            'total_requests' => $totalCombinedRequests,
            'global_throughput_rps' => $combinedRps,
            'global_latency' => [
                'avg_ms' => $globalAvgLatency,
                'p95_ms' => $globalP95Latency,
                'p99_ms' => $globalP99Latency,
            ],
            'server_resources' => [
                'cpu_avg_percent' => $avgCpu,
                'cpu_peak_percent' => $maxCpu,
                'ram_avg_percent' => $avgRam,
                'ram_peak_percent' => $maxRam,
                'redis_available' => RedisQuizService::isAvailable(),
            ],
            'modules' => [
                'quiz_joining'       => $testJoin ? $joinStats : null,
                'waiting_page'       => $testWaiting ? $waitingStats : null,
                'start_counter'      => $testStartCounter ? $startCounterStats : null,
                'questions_load'     => $testQuestions ? $questionsStats : null,
                'sound_load'         => $testSounds ? $soundsStats : null,
                'answer_submissions' => $testAnswers ? $answersStats : null,
                'review_counter'     => $testReviewCounter ? $reviewCounterStats : null,
                'answer_sheet'       => $testAnswerSheet ? $answerSheetStats : null,
                'update_play_data'   => $testUpdatePlayData ? $updatePlayDataStats : null,
                'leaderboard_page'   => $testLeaderboard ? $leaderboardStats : null,
            ],
        ];

        try {
            $dir = dirname($outputPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($outputPath, json_encode($reportData, JSON_PRETTY_PRINT));
            $this->info("Complete JSON Report exported to: {$outputPath}");
        } catch (\Throwable $e) {
            $this->warn("Could not save JSON report: " . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
