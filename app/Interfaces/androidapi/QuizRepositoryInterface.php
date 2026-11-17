<?php

namespace App\Interfaces\androidapi;

interface QuizRepositoryInterface
{
    public function availableList($user): array;
    public function upcomingList($user): array;
    public function practiceList($user): array;
    public function detail($user, int $quizId): array;
    public function join($user, int $quizId, ?string $password): array;
    public function start($user, int $quizId): array;
    public function getQuestion($user, int $quizId, array $data): array;
    public function getQuestionOptions($user, int $quizId, array $data): array;
    public function submitAnswer($user, int $quizId, array $data): array;
    public function submitBulk($user, int $quizId, array $answers): array;
    public function myResult($user, int $quizId): array;
    public function winners(int $quizId): array;
    public function myHistory($user): array;
    public function leave($user, int $quizId): array;
    public function answerSheet($user, int $quizId): array;
    public function useLifeline($user, int $quizId, array $data): array;
    public function quizResult($quizId);
    public function generateQuizWinnerLeaderboard(int $quizId);
    public function quizResultAsync($quizId);   
    public function quizLeaderboard($quizId = null);
    public function quizLeaderboardAsync($quizId = null);

    // FSR App Web & Mobile API Repository Methods
    public function getSoundsData(): array;
    public function preloadQuizQuestionsToSession(int $quizId): array;
    public function getQuestionsFromSessionData(int $quizId): array;
    public function joinSubmitData($user, int $quizId): array;
    public function toggleNotificationData($user, int $quizId): array;
    public function submitQuizAnswer($user, int $quizId, array $data): array;
    public function reconcileQuizAnswers($user, int $quizId, array $clientAnswers): array;
    public function recordLifelineUseData($user, int $quizId, string $lifeline, $questionId): array;
    public function getAnswerSheetDataForApp($user, int $quizId): array;
    public function getLeaderboardDataForApp($user, int $quizId): array;
    public function leaderboardUserUpdate(?int $tenantId = null): void;
    public function getStatusFromSessionData(int $quizId): array;
    public function clearQuizCacheAndSessionData(int $quizId): void;
    public function markUserOutOfQuizData($user, int $quizId, string $reason): array;
    public function quizUpdatePlayData(\App\Models\Quiz $quiz, bool $isCron = false): array;
    public function generateQuizWinner(): array;
    public function hasUserAnsweredLastQuestion($user, int $quizId): bool;
    public function getQuizAnswerExpiryTime($quiz): ?\Carbon\Carbon;
    public function isQuizAnswerExpired($quiz, $user = null): bool;
}
