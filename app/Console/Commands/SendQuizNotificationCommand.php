<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\QuizNotificationRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendQuizNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quiz:send-whatsapp-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send pending WhatsApp notifications for upcoming quizzes using quiz_notifications table';

    /**
     * Execute the console command.
     */
    public function handle(QuizNotificationRepositoryInterface $quizNotificationRepository): int
    {
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        if (\App\Models\Quiz::isAnyQuizPlayingNow()) {
            $this->info("Skipped: A quiz is currently active or calculating winners.");
            return Command::SUCCESS;
        }

        try {
            // 1. Quiz Notifications Prepare (Advance me prepare karein)
            // $quizNotificationRepository->prepareWhatsappEntries(fn($msg) => $this->info($msg));
           
            // 2. Process & Send Pending Notifications
            // $quizNotificationRepository->processPendingQuizNotifications(fn($msg) => $this->info($msg));
            
            $quizNotificationRepository->sendQuizNotifications(fn($msg) => $this->info($msg));

        } catch (\Throwable $e) {
            Log::error('SendQuizNotificationCommand failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Error: ' . $e->getMessage());
        } finally {
            if (isset($originalMemoryLimit) && $originalMemoryLimit !== false && $originalMemoryLimit !== '') {
                ini_set('memory_limit', $originalMemoryLimit);
            }
            gc_collect_cycles();
        }

        return Command::SUCCESS;
    }
}


