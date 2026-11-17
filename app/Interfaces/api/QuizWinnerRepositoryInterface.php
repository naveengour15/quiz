<?php

namespace App\Interfaces\api;

use App\Models\Quiz;

interface QuizWinnerRepositoryInterface
{
    public function generateQuizWinnerLeaderboard(int $quizId);
    public function quizUpdatePlayData(Quiz $quiz, bool $isCron = false): array;
    public function calculateQuizQuestionUserAnswer($quizId, $tenant_id);
    public function updateQuizWinnersData($quizId, $tenant_id, $calculateQuizQuestionUserAnswer);
    public function updateQuizRewards($quizId, $tenant_id, $quiz);
    public function assignVoucherToWinner(array $data);
    public function updateLeaderboardQuizPoint($quizId, $winner, $quiz);
    public function updateQuizLeaderboardHistory($quizId, $winner, $quiz);
    public function quizStatusUpdate($quiz);
    public function generateQuizWinner(): array;
    public function migrateFffDataToPermanentTables(int $quizId): array;
    public function actionTrailLog($tenantId, $tableName, $referenceId, $action, $details = null, $oldDetails = null, $quizId = null, $userId = null);
}
