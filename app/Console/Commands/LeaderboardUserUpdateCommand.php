<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
// use App\Interfaces\api\QuizRepositoryInterface;
use App\Interfaces\api\LeaderboardUserUpdateRepositoryInterface;
use App\Models\Leaderboard;
use Illuminate\Support\Facades\Log;

class LeaderboardUserUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaderboard:user-update {tenant_id? : Optional tenant ID to update leaderboard for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update users ranking and leaderboard table using QuizRepository::leaderboardUserUpdate';

    /**
     * Execute the console command.
     */
    public function handle(LeaderboardUserUpdateRepositoryInterface $leaderboardUserUpdateRepository): int
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        if (\App\Models\Quiz::isAnyQuizPlayingNow()) {
            $this->info("Skipped: A quiz is currently active or calculating winners.");
            return Command::SUCCESS;
        }

        $tenantArg = 1;

        try {
            $this->info('Starting leaderboard user rankings update...');

            $tenantId = (int) $tenantArg;
            $this->line("Processing leaderboard update for tenant ID: {$tenantId}");
            $leaderboardUserUpdateRepository->leaderboardUserUpdate($tenantId);
            $this->info("Leaderboard update completed for tenant ID: {$tenantId}.");
        
            Log::info('Leaderboard user update cron command executed successfully.', [
                'tenant_id' => $tenantArg ?? 'all',
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Leaderboard user update command failed', [
                'tenant_id' => $tenantArg ?? null,
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            $this->error("Leaderboard user update failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
