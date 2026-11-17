<?php

namespace App\Repositories;

interface QuizNotificationRepositoryInterface
{
    /**
     * Prepare QuizNotification database entries for upcoming scheduled quizzes.
     *
     * @param callable|null $output Optional callback for logging/console output (e.g. fn($msg) => ...)
     * @return array Summary of preparation ['inserted' => int, 'updated' => int, 'quiz' => mixed]
     */
    public function prepareWhatsappEntries(?callable $output = null): array;

    /**
     * Process pending quiz notifications in parallel batches.
     *
     * @param callable|null $output Optional callback for logging/console output (e.g. fn($msg) => ...)
     * @return array Summary of processed notifications ['sent' => int, 'failed' => int]
     */
    public function processPendingQuizNotifications(?callable $output = null): array;

    /**
     * Prepare QuizNotification database entries for a specific scheduled quiz.
     *
     * @param int $quizId
     * @return array Summary ['inserted' => int, 'updated' => int, 'quiz' => mixed]
     */
    public function prepareWhatsappQuizEntries(int $quizId): array;

    /**
     * Send pending quiz notifications in parallel batches.
     *
     * @param callable|null $output Optional callback for logging/console output (e.g. fn($msg) => ...)
     * @return array Summary of processed notifications ['sent' => int, 'failed' => int]
     */
    public function sendQuizNotifications(?callable $output = null): array;
}
