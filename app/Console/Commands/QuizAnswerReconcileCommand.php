<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RedisQuizService;
use Illuminate\Support\Facades\Log;

class QuizAnswerReconcileCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'quiz:reconcile-answers {--daemon : Run continuously as a background daemon} {--duration=55 : Seconds to run when not daemon} {--interval=5 : Seconds between batches (default 5)}';

    /**
     * The console command description.
     */
    protected $description = 'Reconcile pending quiz answers from Redis to MySQL in small intervals';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDaemon = (bool) $this->option('daemon');
        $duration = (int) $this->option('duration');
        $interval = max(1, (int) $this->option('interval'));

        $start = time();

        while ($isDaemon || (time() - $start) < $duration) {
            try {
                $synced = RedisQuizService::collectAndBulkUpsertPendingAnswers(5000);
                if ($synced > 0) {
                    $this->info("[Interval {$interval}s] Synced {$synced} pending answers.");
                }
            } catch (\Throwable $e) {
                Log::error('QuizAnswerReconcileCommand error: ' . $e->getMessage());
            }

            usleep($interval * 1000000);
        }

        $this->info('QuizAnswerReconcileCommand completed.');
        return Command::SUCCESS;
    }
}

