<?php

namespace App\Console\Commands;

use App\Repositories\ProcessReferralRewardsRepositoryInterface;
use Illuminate\Console\Command;

class ProcessReferralRewardsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referral:process-rewards {--dry-run : Simulate execution without saving database changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reward 10 coins to referrer when a referred user signs up and completes their first quiz (question_id > 0) using referral_codes table';

    /**
     * Execute the console command.
     */
    public function handle(ProcessReferralRewardsRepositoryInterface $referralRepository): int
    {
        if (\App\Models\Quiz::isAnyQuizPlayingNow()) {
            $this->info("Skipped: A quiz is currently active or calculating winners.");
            return Command::SUCCESS;
        }

        $isDryRun = (bool) $this->option('dry-run');

        $referralRepository->processRewards($isDryRun, function (string $type, string $message) {
            switch ($type) {
                case 'warn':
                    $this->warn($message);
                    break;
                case 'error':
                    $this->error($message);
                    break;
                case 'info':
                    $this->info($message);
                    break;
                default:
                    $this->line($message);
                    break;
            }
        });

        return Command::SUCCESS;
    }
}
