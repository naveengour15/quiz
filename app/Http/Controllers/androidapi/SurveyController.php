<?php

namespace App\Http\Controllers\androidapi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\androidapi\Traits\ResolvesAndroidUser;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SurveyController extends Controller
{
    use ResolvesAndroidUser;

    public function getSurveys(Request $request)
    {
        $user = $this->getAuthUser($request);
        $tenantId = $request->header('X-Tenant-Id') ?? 1;

        $surveys = Survey::whereIn('tenant_id', [$tenantId, 1])
            ->where('status', 'active')
            ->latest('id')
            ->get();

        $surveysDone = 0;
        $surveyPoints = 0;
        $quizzesPlayed = 0;
        $completedSurveyIds = [];
        $startedSurveyIds = [];

        if ($user) {
            $tenantList = array_unique([$tenantId, 1]);
            $tenantFilter = function ($q) use ($tenantList) {
                $q->whereIn('tenant_id', $tenantList)->orWhereNull('tenant_id');
            };

            $surveysDone = (int) DB::table('survey_user_progress')
                ->where('user_id', $user->id)
                ->where($tenantFilter)
                ->where('status', 'completed')
                ->count();

            $surveyPoints = (int) round((float) DB::table('survey_rewards')
                ->where('user_id', $user->id)
                ->where($tenantFilter)
                ->sum('point'));

            $quizzesPlayed = (int) round((float) DB::table('survey_rewards')
                ->where('user_id', $user->id)
                ->where($tenantFilter)
                ->sum('quiz'));

            $completedSurveyIds = DB::table('survey_user_progress')
                ->where('user_id', $user->id)
                ->where($tenantFilter)
                ->where('status', 'completed')
                ->pluck('survey_id')
                ->map(fn($id) => (int)$id)
                ->toArray();

            $startedSurveyIds = DB::table('survey_user_progress')
                ->where('user_id', $user->id)
                ->where($tenantFilter)
                ->where('status', 'pending')
                ->pluck('survey_id')
                ->map(fn($id) => (int)$id)
                ->toArray();
        }

        $rawBanners = \App\Models\Banner::where('status', 'active')
            ->whereIn('tenant_id', [$tenantId, 1])
            ->latest('id')
            ->get();
        $banners = $rawBanners->map(function ($b) {
            return [
                'id' => $b->id,
                'name' => $b->name,
                'banner_url' => $b->banner_url,
                'image' => $b->banner_url,
                'url' => $b->url,
                'type' => $b->type ?? ['all'],
            ];
        });

        return response()->json([
            'success' => true,
            'surveys' => $surveys,
            'banners' => $banners,
            'summary' => [
                'surveys_done' => $surveysDone,
                'survey_points' => $surveyPoints,
                'quizzes_played' => $quizzesPlayed,
                'completed_ids' => $completedSurveyIds,
                'started_ids' => $startedSurveyIds
            ]
        ]);
    }

    public function playSurvey(Request $request, $id)
    {
        $survey = Survey::with('questions')->find($id);
        if (!$survey) {
            return response()->json(['success' => false, 'message' => 'Survey not found.'], 404);
        }

        $user = $this->getAuthUser($request);
        $userId = $user?->id;

        $existingAnswers = DB::table('survey_answers')
            ->where('survey_id', $id)
            ->where('user_id', $userId)
            ->pluck('answer', 'question_id')
            ->toArray();

        $totalQuestions = $survey->questions->count();
        $answeredCount = count($existingAnswers);
        $status = ($answeredCount >= $totalQuestions && $totalQuestions > 0) ? 'completed' : 'pending';

        if ($userId) {
            DB::table('survey_user_progress')->updateOrInsert(
                ['survey_id' => $id, 'user_id' => $userId],
                [
                    'tenant_id' => $survey->tenant_id,
                    'total_number_question' => $totalQuestions,
                    'question_number' => $answeredCount,
                    'status' => $status,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'survey' => $survey,
            'existing_answers' => $existingAnswers,
            'answered_count' => $answeredCount,
            'status' => $status
        ]);
    }

    public function submitSurvey(Request $request, $id)
    {
        try {
            $survey = Survey::find($id);
            if (!$survey) {
                return response()->json(['success' => false, 'message' => 'Survey not found.'], 404);
            }

            $user = $this->getAuthUser($request);
            $userId = $user?->id;
            $tenantId = $survey->tenant_id ?? 1;

            $answers = $request->input('answers', []);
            if ($request->has('question_id') && $request->has('answer')) {
                $answers[] = [
                    'question_id' => $request->input('question_id'),
                    'answer' => $request->input('answer')
                ];
            }

            foreach ($answers as $ans) {
                if (empty($ans['question_id'])) continue;

                DB::table('survey_answers')->updateOrInsert(
                    [
                        'tenant_id' => $tenantId,
                        'survey_id' => $id,
                        'user_id' => $userId,
                        'question_id' => $ans['question_id']
                    ],
                    [
                        'answer' => $ans['answer'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            $totalQuestions = $survey->questions()->count();
            $answeredQuestions = DB::table('survey_answers')->where([
                'survey_id' => $id,
                'user_id' => $userId
            ])->count();

            $isCompleted = ($answeredQuestions >= $totalQuestions && $totalQuestions > 0);
            $status = $isCompleted ? 'completed' : 'pending';

            if ($userId) {
                DB::table('survey_user_progress')->updateOrInsert(
                    ['survey_id' => $id, 'user_id' => $userId],
                    [
                        'tenant_id' => $tenantId,
                        'total_number_question' => $totalQuestions,
                        'question_number' => $answeredQuestions,
                        'status' => $status,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            $rewardGiven = false;
            $rewardPoints = (int) ($survey->point ?? 0);
            $rewardQuiz = (int) ($survey->quiz ?? 0);

            if ($isCompleted && $userId) {
                $hasReward = DB::table('survey_rewards')->where([
                    'survey_id' => $id,
                    'user_id' => $userId
                ])->exists();

                if (!$hasReward) {
                    DB::table('survey_rewards')->insert([
                        'tenant_id' => $tenantId,
                        'survey_id' => $id,
                        'user_id' => $userId,
                        'reward_type' => $survey->reward_type,
                        'point' => $rewardPoints,
                        'quiz' => $rewardQuiz,
                        'status' => 'settled',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $rewardGiven = true;

                    if ($survey->reward_type === 'quiz' || ($rewardQuiz > 0 && empty($survey->reward_type))) {
                        if ($rewardQuiz > 0) {
                            DB::table('users')->where('id', $userId)->increment('available_quiz_limit', $rewardQuiz);
                        }
                    }

                    if ($survey->reward_type === 'point' && $rewardPoints > 0) {
                        DB::table('users')->where('id', $userId)->increment('wallet_balance', $rewardPoints);

                        DB::table('wallet_history')->insert([
                            'tenant_id' => $tenantId,
                            'user_id' => $userId,
                            'amount' => $rewardPoints,
                            'type' => 'credit',
                            'transaction_type' => 'survey_reward',
                            'reference_id' => (string) $id,
                            'remarks' => 'Reward for completing survey: ' . ($survey->name ?? $survey->title ?? ('Survey #' . $id)),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            $updatedQuizLimit = $userId ? DB::table('users')->where('id', $userId)->value('available_quiz_limit') : 0;

            return response()->json([
                'success' => true,
                'reward_given' => $rewardGiven,
                'reward_type' => $survey->reward_type,
                'points' => $rewardPoints,
                'quiz' => $rewardQuiz,
                'available_quiz_limit' => $updatedQuizLimit,
                'completed' => $isCompleted,
                'question_number' => $answeredQuestions,
                'total_number_question' => $totalQuestions,
                'status' => $status
            ]);
        } catch (\Throwable $e) {
            Log::error('Survey submit error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
