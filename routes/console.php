<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Quiz;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Declares winners and marks quiz as completed (Must always run to finalize quizzes)
Schedule::command('quiz:result')
    ->everyMinute()
    ->withoutOverlapping();

// Paused during active quiz play; resumes automatically once winners are generated
Schedule::command('quiz:send-whatsapp-notifications')
    ->everyMinute()
    ->withoutOverlapping()
    ->skip(fn() => Quiz::isAnyQuizPlayingNow());

// Paused during active quiz play; resumes automatically once winners are generated
Schedule::command('quiz:send-firebase-notifications')
    ->everyMinute()
    ->withoutOverlapping()
    ->skip(fn() => Quiz::isAnyQuizPlayingNow());

// Paused during active quiz play; resumes automatically once winners are generated
Schedule::command('redeem:send-whatsapp-alerts')
    ->everyMinute()
    ->withoutOverlapping()
    ->skip(fn() => Quiz::isAnyQuizPlayingNow());

// Paused during active quiz play; resumes automatically once winners are generated
Schedule::command('referral:process-rewards')
    ->everyMinute()
    ->withoutOverlapping()
    ->skip(fn() => Quiz::isAnyQuizPlayingNow());

// Paused during active quiz play; resumes automatically once winners are generated
Schedule::command('leaderboard:user-update')
    ->everyMinute()
    ->withoutOverlapping()
    ->skip(fn() => Quiz::isAnyQuizPlayingNow());

// Answers collection queue worker (Required during gameplay for Redis -> MySQL sync)
Schedule::command('quiz:collect-answers')
    ->everyMinute()
    ->withoutOverlapping();