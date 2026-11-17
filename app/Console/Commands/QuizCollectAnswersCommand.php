<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RedisQuizService;
use Illuminate\Support\Facades\Log;

class QuizCollectAnswersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quiz:collect-answers {--daemon : Run as continuous background daemon} {--duration=55 : Seconds to run when executed via scheduler} {--interval=5 : Batch interval in seconds (default: 5s)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collect pending quiz answers from Redis and bulk upsert into MySQL in 5-second batches (12 batches per minute)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        $duration = (int) ($this->option('duration') ?: 55);
        $isDaemon = (bool) $this->option('daemon');

        // Default batch interval: 5 seconds (optimal for 50-250 answers/batch to minimize MySQL lock holding time)
        $defaultInterval = 5;

        $intervalSec = max(1, (int) ($this->option('interval') ?: $defaultInterval));
        $intervalMicro = $intervalSec * 1000000;
        $startTime = time();

        $this->info("QuizCollectAnswersCommand started (daemon: " . ($isDaemon ? 'yes' : 'no') . ", duration: {$duration}s, batch interval: {$intervalSec}s)...");

        while ($isDaemon || (time() - $startTime) < $duration) {
            try {
                // 1. Drain pending enrollments queue in batches of 5000
                $totalEnrollmentsSynced = 0;
                do {
                    $syncedEnr = RedisQuizService::collectAndBulkUpsertPendingEnrollments(5000);
                    $totalEnrollmentsSynced += $syncedEnr;
                } while ($syncedEnr >= 5000);

                if ($totalEnrollmentsSynced > 0) {
                    $this->line("[" . now()->toTimeString() . "] Bulk upserted {$totalEnrollmentsSynced} quiz enrollments into MySQL.");
                }

                // 2. Drain pending answers queue in batches of 5000 to handle 50,000+ concurrent users per question cycle
                $totalSyncedThisCycle = 0;
                do {
                    $synced = RedisQuizService::collectAndBulkUpsertPendingAnswers(5000);
                    $totalSyncedThisCycle += $synced;
                } while ($synced >= 5000);

                if ($totalSyncedThisCycle > 0) {
                    $this->line("[" . now()->toTimeString() . "] Bulk upserted {$totalSyncedThisCycle} answers into MySQL in this {$intervalSec}s batch.");
                }
            } catch (\Throwable $e) {
                Log::error("QuizCollectAnswersCommand iteration error: " . $e->getMessage());
                $this->error("Error collecting quiz answers/enrollments: " . $e->getMessage());
            }

            // Sleep 14 seconds (1 batch per question window) before next batch collection
            usleep($intervalMicro);
        }

        $this->info("QuizCollectAnswersCommand batch cycle completed.");
        return Command::SUCCESS;
    }
}
