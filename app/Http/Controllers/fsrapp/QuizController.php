<?php

namespace App\Http\Controllers\fsrapp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Events\QuizStarted;
use App\Events\TestBroadcast;

use App\Interfaces\api\QuizRepositoryInterface;

class QuizController extends Controller
{
    public function waiting($id)
    {
        return redirect()->route('fsrapp.quizes.join', $id);
    }

    public function play($id)
    {
        return redirect()->route('fsrapp.quizes.start-counter', $id);
    }

    public function review($id)
    {
        return redirect()->route('fsrapp.quizes.review-counter', $id);
    }

    public function result($id)
    {
        $quiz = Quiz::find($id);
        if ($quiz && !$quiz->isAnswerTimeFinished()) {
            return redirect()->route('fsrapp.home');
        }
        return redirect()->route('fsrapp.quizes.answer-sheet', $id);
    }

    public function winners($id)
    {
        return redirect()->route('fsrapp.quizes.leaderboard', $id);
    }

    public function getWinners(QuizRepositoryInterface $quizRepository, int $quizId)
    {
        return response()->json($quizRepository->winners($quizId));
    }

    // Test page to demonstrate broadcasts
    public function testPage($id)
    {
        return view('fsrapp.quiz.test', compact('id'));
    }

    // Trigger a test broadcast (GET for convenience during development)
    public function testBroadcast($id)
    {
        $server_now_ms = (int) Carbon::now()->valueOf();
        $message = "Test broadcast from server at {$server_now_ms}";

        broadcast(new TestBroadcast($id, $message, $server_now_ms))->toOthers();

        return response()->json([
            'status' => 'ok',
            'message' => $message,
            'server_now_ms' => $server_now_ms,
        ]);
    }

    /**
     * Start the quiz (broadcast start time and duration).
     */
    public function startQuiz(Request $request, $id)
    {
        $durationSeconds = (int) $request->input('duration_seconds', 60);
        $duration_ms = $durationSeconds * 1000;

        $start_time_ms = (int) Carbon::now()->valueOf();
        $server_now_ms = $start_time_ms;

        $quiz = \App\Models\Quiz::find($id);
        $totalQuestions = ($quiz && $quiz->question_limit > 0)
            ? (int) $quiz->question_limit
            : (\App\Models\QuizQuestion::where('quiz_id', $id)->where(function($q) { $q->whereNull('is_switch_question')->orWhere('is_switch_question', 0); })->count() ?: 10);

        $key = "fsrapp:quiz:{$id}:start";
        Cache::put($key, [
            'start_time_ms' => $start_time_ms,
            'duration_ms' => $duration_ms,
            'total_questions' => $totalQuestions,
        ], now()->addMinutes(120));

        broadcast(new QuizStarted($id, $start_time_ms, $duration_ms, $server_now_ms))->toOthers();

        return response()->json([
            'status' => 'started',
            'quiz_id' => (int) $id,
            'start_time_ms' => $start_time_ms,
            'duration_ms' => $duration_ms,
            'server_now_ms' => $server_now_ms,
            'total_questions' => $totalQuestions,
            'warmup_seconds' => 10,
            'per_question_seconds' => 15,
            'hold_seconds' => 5,
            'answer_seconds' => 10,
            'anssheet_seconds' => 10,
            'leaderboard_wait_seconds' => 30,
        ]);
    }

    /**
     * Return quiz status so late joiners can sync immediately.
     */
    public function status($id)
    {
        $server_now_ms = (int) Carbon::now()->valueOf();
        $key = "fsrapp:quiz:{$id}:start";
        $val = Cache::get($key);

        $quiz = \App\Models\Quiz::find($id);
        $totalQuestions = ($quiz && $quiz->question_limit > 0)
            ? (int) $quiz->question_limit
            : (\App\Models\QuizQuestion::where('quiz_id', $id)->where(function($q) { $q->whereNull('is_switch_question')->orWhere('is_switch_question', 0); })->count() ?: 10);

        if (!$val) {
            $start_time_ms = null;

            if ($quiz) {
                if ($quiz->scheduled_at) {
                    $start_time_ms = (int) Carbon::parse($quiz->scheduled_at)->valueOf();
                } elseif ($quiz->availability_start) {
                    $start_time_ms = (int) Carbon::parse($quiz->availability_start)->valueOf();
                }
            }

            if (!$start_time_ms) {
                $start_time_ms = $server_now_ms;
            }

            $val = [
                'start_time_ms' => $start_time_ms,
                'duration_ms' => 0,
                'total_questions' => $totalQuestions,
            ];

            Cache::put($key, $val, now()->addMinutes(120));
        }

        $setting = \App\Models\QuizSetting::first();

        // Retrieve QuizStatus directly from session
        $quizStatusData = \App\Models\QuizStatus::getStatusFromSession($id);

        return response()->json([
            'started' => true,
            'quiz_id' => (int) $id,
            'start_time_ms' => $val['start_time_ms'],
            'duration_ms' => $val['duration_ms'] ?? 0,
            'server_now_ms' => $server_now_ms,
            'total_questions' => $totalQuestions,
            'warmup_seconds' => 10,
            'per_question_seconds' => 15,
            'hold_seconds' => 5,
            'answer_seconds' => 10,
            'anssheet_seconds' => 10,
            'leaderboard_wait_seconds' => 30,
            'sound_settings' => $setting ? $setting->getSoundUrls() : null,
            'quiz_status' => $quizStatusData,
            'status_from_session' => session()->has("quiz_{$id}_status"),
        ]);
    }

    public function soundSettings()
    {
        $sounds = \Illuminate\Support\Facades\Cache::remember('global_quiz_sound_urls', 86400, function () {
            $setting = \App\Models\QuizSetting::first();
            return $setting ? $setting->getSoundUrls() : null;
        });

        return response()->json([
            'status' => 'ok',
            'sound_settings' => $sounds
        ]);
    }
}
