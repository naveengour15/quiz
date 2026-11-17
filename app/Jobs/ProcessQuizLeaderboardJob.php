<?php

namespace App\Jobs;

use App\Interfaces\api\QuizRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessQuizLeaderboardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?int $quizId;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $quizId = null)
    {
        $this->quizId = $quizId;
    }

    /**
     * Execute the job.
     */
    public function handle(QuizRepositoryInterface $quizRepository): void
    {
        try {
            Log::info("ProcessQuizLeaderboardJob started for quiz_id: " . ($this->quizId ?? 'all'));
            $quizRepository->quizLeaderboard($this->quizId);
            Log::info("ProcessQuizLeaderboardJob completed for quiz_id: " . ($this->quizId ?? 'all'));
        } catch (\Throwable $e) {
            Log::error("ProcessQuizLeaderboardJob failed for quiz_id: " . ($this->quizId ?? 'all'), [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
        }
    }
}
