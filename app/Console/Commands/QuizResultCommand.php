<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Interfaces\api\QuizWinnerRepositoryInterface;
use App\Models\Quiz;
use Illuminate\Support\Facades\Log;

class QuizResultCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'quiz:result {quiz_id?}';

    /**
     * The console command description.
     */
    protected $description = 'Process completed quiz cron jobs and declare winner results';

    public function handle(QuizWinnerRepositoryInterface $quizWinnerRepository)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        try {
            $quizId = $this->argument('quiz_id');

            if ($quizId) {
                $quiz = Quiz::find($quizId);
                if ($quiz && $quiz->complete_status == 'processing') {
                    $result = $quizWinnerRepository->quizUpdatePlayData($quiz);
                    $this->info("Quiz {$quizId} processed: " . ($result['message'] ?? 'OK'));
                } elseif ($quiz && $quiz->complete_status != 'processing') {
                    $this->error("Quiz {$quizId} is not in processing state.");
                } else {
                    $this->error("Quiz {$quizId} not found.");
                }
            } else {
                $result = $quizWinnerRepository->generateQuizWinner();
                $this->info('Quiz winner cron executed successfully. Processed: ' . ($result['processed'] ?? 0));
            }

            Log::info('Quiz Result Command executed successfully.', ['quiz_id' => $quizId]);
        } catch (\Exception $e) {
            Log::error('Quiz Result Command Failed', [
                'quiz_id' => $quizId ?? null,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            $this->error($e->getMessage());
        }

        return Command::SUCCESS;
    }
}