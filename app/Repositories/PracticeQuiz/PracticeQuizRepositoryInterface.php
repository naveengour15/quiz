<?php

namespace App\Repositories\PracticeQuiz;

interface PracticeQuizRepositoryInterface
{
    public function availableList($user): array;
    public function detail($user, int $quizId): array;
    public function start($user, int $quizId): array;
    public function getQuestion($user, int $quizId, array $payload): array;
    public function submitAnswer($user, int $quizId, array $payload): array;
    public function useLifeline($user, int $quizId, string $lifeline, int $questionId): array;
    public function myResult($user, int $quizId): array;
    public function answerSheet($user, int $quizId): array;
}
