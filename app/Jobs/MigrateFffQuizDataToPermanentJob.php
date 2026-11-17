<?php

namespace App\Jobs;

use App\Interfaces\api\QuizWinnerRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MigrateFffQuizDataToPermanentJob implements ShouldQueue
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
     * Execute the job to migrate FFF temporary table data into permanent tables and clean up.
     */
    public function handle(QuizWinnerRepositoryInterface $quizWinnerRepository): void
    {
        try {
            Log::info("MigrateFffQuizDataToPermanentJob started for quiz_id: {$this->quizId}");

            $result = $quizWinnerRepository->migrateFffDataToPermanentTables($this->quizId);

            if ($result['success'] ?? false) {
                Log::info("MigrateFffQuizDataToPermanentJob completed successfully for quiz_id: {$this->quizId}");
            } else {
                Log::warning("MigrateFffQuizDataToPermanentJob finished with warning for quiz_id: {$this->quizId}", [
                    'message' => $result['message'] ?? 'Unknown error'
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("MigrateFffQuizDataToPermanentJob failed for quiz_id: {$this->quizId}", [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }
}
