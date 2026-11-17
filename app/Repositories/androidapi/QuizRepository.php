<?php

namespace App\Repositories\androidapi;

use App\Interfaces\androidapi\QuizRepositoryInterface;
use App\Interfaces\androidapi\QuizWinnerRepositoryInterface;
use App\Interfaces\ActionTrailRepositoryInterface;
use App\Models\Quiz;
use App\Repositories\androidapi\Traits\HandlesQuizLeaderboardAndRewards;
use App\Repositories\androidapi\Traits\HandlesQuizLists;
use App\Repositories\androidapi\Traits\HandlesQuizPlay;
use App\Repositories\androidapi\Traits\HandlesQuizWebApp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QuizRepository implements QuizRepositoryInterface
{
    use HandlesQuizLists,
        HandlesQuizPlay,
        HandlesQuizLeaderboardAndRewards,
        HandlesQuizWebApp;

    public function __construct(protected ActionTrailRepositoryInterface $actontrail)
    {
    }

    public function actionTrailLog(
        $tenantId,
        $tableName,
        $referenceId,
        $action,
        $newDetails = null,
        $oldDetails = null,
        $quizId = null,
        $userId = null,
        $surveyId = null
    ) {
        $newDetailsArr = null;
        if ($newDetails !== null) {
            if (is_object($newDetails) && method_exists($newDetails, 'toArray')) {
                $newDetailsArr = $newDetails->toArray();
            } elseif (is_array($newDetails)) {
                $newDetailsArr = $newDetails;
            }
        }

        if (!$quizId) {
            if (is_array($newDetailsArr) && !empty($newDetailsArr['quiz_id'])) {
                $quizId = $newDetailsArr['quiz_id'];
            } elseif (is_object($newDetails) && !empty($newDetails->quiz_id)) {
                $quizId = $newDetails->quiz_id;
            } elseif ($tableName === 'quizzes' && $referenceId) {
                $quizId = $referenceId;
            }
        }

        if (!$userId) {
            if (is_array($newDetailsArr) && !empty($newDetailsArr['user_id'])) {
                $userId = $newDetailsArr['user_id'];
            } elseif (is_object($newDetails) && !empty($newDetails->user_id)) {
                $userId = $newDetails->user_id;
            } elseif ($tableName === 'users' && $referenceId) {
                $userId = $referenceId;
            } elseif (is_array($newDetailsArr) && !empty($newDetailsArr['updated_by'])) {
                $userId = $newDetailsArr['updated_by'];
            } else {
                $userId = auth()->id() ?: session('fsr_user_id');
            }
        }

        if (!$surveyId) {
            if (is_array($newDetailsArr) && !empty($newDetailsArr['survey_id'])) {
                $surveyId = $newDetailsArr['survey_id'];
            } elseif (is_object($newDetails) && !empty($newDetails->survey_id)) {
                $surveyId = $newDetails->survey_id;
            } elseif ($tableName === 'surveys' && $referenceId) {
                $surveyId = $referenceId;
            }
        }

        return $this->actontrail->create([
            'tenant_id'    => $tenantId ?? 1,
            'quiz_id'      => $quizId,
            'user_id'      => $userId,
            'survey_id'    => $surveyId,
            'table_name'   => $tableName,
            'reference_id' => $referenceId,
            'action'       => $action,
            'updated_by'   => $userId ?: (auth()->id() ?: session('fsr_user_id')),
            'details'      => $newDetailsArr ?? $newDetails,
            'old_details'  => $oldDetails
        ]);
    }

    public function generateQuizWinnerLeaderboard(int $quizId)
    {
        return app(QuizWinnerRepositoryInterface::class)->generateQuizWinnerLeaderboard($quizId);
    }

    public function quizUpdatePlayData(Quiz $quiz, bool $isCron = false): array
    {
        return app(QuizWinnerRepositoryInterface::class)->quizUpdatePlayData($quiz, $isCron);
    }

    public function generateQuizWinner(): array
    {
        return app(QuizWinnerRepositoryInterface::class)->generateQuizWinner();
    }

    public function calculateUser($quiz)
    {
        try {
            if (is_numeric($quiz)) {
                $quiz = Quiz::find($quiz);
            }
            if (!$quiz) {
                return false;
            }
            if (!in_array($quiz->complete_status, ['processing', 'completed'])) {
                $quiz->update(['complete_status' => 'processing']);
            }
            $res = app(QuizWinnerRepositoryInterface::class)->quizUpdatePlayData($quiz);
            return !empty($res['success']);
        } catch (\Throwable $e) {
            Log::error('calculateUser execution failed: ' . $e->getMessage());
            return false;
        }
    }

    public function quizResult($quizId)
    {
        try {
            $quiz = Quiz::find($quizId);
            if (!$quiz) {
                return false;
            }
            if (!in_array($quiz->complete_status, ['processing', 'completed'])) {
                $quiz->update(['complete_status' => 'processing']);
            }
            return app(QuizWinnerRepositoryInterface::class)->quizUpdatePlayData($quiz);
        } catch (\Throwable $e) {
            Log::error('QuizResult execution failed', [
                'quiz_id' => $quizId,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function quizResultAsync($quizId)
    {
        $quizId = (int) $quizId;
        if (!$quizId) return;

        $lockKey = "quiz_{$quizId}_process_result_job_lock";
        $lock = Cache::lock($lockKey, 30);
        if (!$lock->get()) {
            return;
        }

        try {
            if (config('queue.default') === 'sync') {
                $this->generateQuizWinnerLeaderboard($quizId);
                return;
            }

            \App\Jobs\ProcessQuizResultJob::dispatch($quizId);
        } catch (\Throwable $e) {
            Log::error('quizResultAsync dispatch failed', [
                'quiz_id' => $quizId,
                'message' => $e->getMessage(),
            ]);
            $this->generateQuizWinnerLeaderboard($quizId);
        }
    }

    public function quizLeaderboard($quizId = null)
    {
        try {
            if ($quizId) {
                return $this->generateQuizWinnerLeaderboard((int) $quizId);
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('quizLeaderboard failed', [
                'quiz_id' => $quizId,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function quizLeaderboardAsync($quizId = null)
    {
        try {
            if ($quizId) {
                \App\Jobs\ProcessQuizLeaderboardJob::dispatch((int) $quizId);
            }
        } catch (\Throwable $e) {
            Log::error('quizLeaderboardAsync dispatch failed', [
                'quiz_id' => $quizId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}