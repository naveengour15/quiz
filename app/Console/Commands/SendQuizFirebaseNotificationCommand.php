<?php

namespace App\Console\Commands;

use App\Services\QuizStartReminderService;
use Illuminate\Console\Command;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SendQuizFirebaseNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quiz:send-firebase-notifications 
                            {quiz_id? : Unused. Kept so older calls do not fail}
                            {--topic= : Unused. Kept so older calls do not fail}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a quiz start reminder to the quiz_updates topic 5 minutes before scheduled quizzes start';

    /**
     * Execute the console command.
     */
    public function handle(QuizStartReminderService $service): int
    {
        try {
            $quizId = $this->argument('quiz_id') ? (int) $this->argument('quiz_id') : null;
            if ($quizId) {
                $data = $service->run($quizId);
                $this->info($service->message($data));
                return self::SUCCESS;
            }

            // Loop every 3 seconds for up to ~54 seconds so 10s and 30s reminders are caught with pinpoint precision
            $startTime = time();
            do {
                $data = $service->run();
                $msg = $service->message($data);
                if ($msg !== 'No upcoming quiz reminders due at this time.') {
                    $this->info($msg);
                }
                if (time() - $startTime >= 54) {
                    break;
                }
                sleep(3);
            } while (true);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
