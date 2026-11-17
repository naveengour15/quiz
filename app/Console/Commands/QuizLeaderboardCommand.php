<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Interfaces\api\QuizWinnerRepositoryInterface;
use Illuminate\Support\Facades\Log;

class QuizLeaderboardCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'quiz:leaderboard {quizId?}';

    /**
     * The console command description.
     */
    protected $description = 'Process completed quizzes and declare leaderboard';

    public function handle(QuizWinnerRepositoryInterface $quizWinnerRepository)
    {
        try {
            // $quizId = $this->argument('quizId');
            // if ($quizId) {
            //     $quiz = \App\Models\Quiz::find($quizId);
            //     if ($quiz) {
            //         if ($quiz->complete_status === 'pending') {
            //             $quiz->update(['complete_status' => 'processing']);
            //         }
            //         $result = $quizWinnerRepository->quizUpdatePlayData($quiz);
            //         $this->info("Quiz {$quizId} leaderboard processed: " . ($result['message'] ?? 'Done'));
            //     } else {
            //         $this->error("Quiz {$quizId} not found.");
            //     }
            // } else {
                $result = $quizWinnerRepository->generateQuizWinner();
                $this->info('Quiz cron executed. Processed: ' . ($result['processed'] ?? 0) . ', Failed: ' . ($result['failed'] ?? 0));
            //}

            Log::info('Quiz Result Cron executed successfully.', ['quiz_id' => $quizId]);
        } catch (\Exception $e) {
            Log::error('Quiz Result Cron Failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            $this->error($e->getMessage());
        }

        return Command::SUCCESS;
    }
}