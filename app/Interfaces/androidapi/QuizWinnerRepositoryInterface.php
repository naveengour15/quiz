<?php

namespace App\Interfaces\androidapi;

interface QuizWinnerRepositoryInterface
{
    public function generateQuizWinnerLeaderboard(int $quizId);
    public function quizUpdatePlayData(\App\Models\Quiz $quiz, bool $isCron = false): array;
    public function generateQuizWinner(): array;
}
