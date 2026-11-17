<?php

namespace App\Jobs;

use App\Interfaces\api\QuizRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessQuizResultJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $quizId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $quizId)
    {
        $this->quizId = $quizId;
    }

    /**
     * Execute the job.
     */
    public function handle(QuizRepositoryInterface $quizRepository): void
    {
        try {
            Log::info("ProcessQuizResultJob started for quiz_id: {$this->quizId}");

            // Flush in-memory Redis answer batches to MySQL if available
            if (\App\Services\RedisQuizService::isAvailable()) {
                \App\Services\RedisQuizService::persistQuizDataToMySQL($this->quizId);
            }

            $quizRepository->generateQuizWinnerLeaderboard($this->quizId);
            Log::info("ProcessQuizResultJob completed for quiz_id: {$this->quizId}");
        } catch (\Throwable $e) {
            Log::error("ProcessQuizResultJob failed for quiz_id: {$this->quizId}", [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
        }
    }
}
