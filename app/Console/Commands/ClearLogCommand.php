<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearLogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all Laravel log files in storage/logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logPath = storage_path('logs');
        $files = File::glob($logPath . '/*.log');

        if (empty($files)) {
            $this->info('No log files found in storage/logs.');
            return Command::SUCCESS;
        }

        foreach ($files as $file) {
            File::put($file, '');
        }

        $this->info('All log files in storage/logs have been cleared successfully!');
        return Command::SUCCESS;
    }
}
