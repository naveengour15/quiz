<?php

namespace App\Http\Controllers\fsrapp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Survey;

class SurveyController extends Controller
{
    public function index()
    {
        $user = Auth::user() ?? \App\Models\User::first();
        $tenantId = session('tenant_id', 1);

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
            $surveysDone = DB::table('survey_user_progress')->where('user_id', $user->id)->where('tenant_id', $tenantId)->where('status', 'completed')->count();
            $surveyPoints = DB::table('survey_rewards')->where('user_id', $user->id)->where('tenant_id', $tenantId)->sum('point');
            // $quizzesPlayed = DB::table('quiz_users')->where('user_id', $user->id)->where('tenant_id', $tenantId)->count();
              $quizzesPlayed = DB::table('survey_rewards')->where('user_id', $user->id)->where('tenant_id', $tenantId)->sum('quiz');
            
            $completedSurveyIds = DB::table('survey_user_progress')->where('user_id', $user->id)->where('tenant_id', $tenantId)->where('status', 'completed')->pluck('survey_id')->toArray();
            $startedSurveyIds = DB::table('survey_user_progress')->where('user_id', $user->id)->where('tenant_id', $tenantId)->where('status', 'pending')->pluck('survey_id')->toArray();
        }

        return view('fsrapp.survey.index', compact(
            'user',
            'surveys',
            'surveysDone',
            'surveyPoints',
            'quizzesPlayed',
            'completedSurveyIds',
            'startedSurveyIds'
        ));
    }

    public function play($id)
    {
        $survey = Survey::with('questions')->findOrFail($id);
        $user = Auth::user() ?? \App\Models\User::first();
        $userId = $user ? $user->id : null;
        
        // Find existing answers if any
        $existingAnswers = DB::table('survey_answers')
            ->where('survey_id', $id)
            ->where('user_id', $userId)
            ->pluck('answer', 'question_id')
            ->toArray();

        $totalQuestions = $survey->questions->count();
        $answeredCount = count($existingAnswers);
        $status = ($answeredCount >= $totalQuestions && $totalQuestions > 0) ? 'completed' : 'pending';

        // Track user progress
        DB::table('survey_user_progress')->updateOrInsert(
            [
                'survey_id' => $id,
                'user_id' => $userId,
            ],
            [
                'tenant_id' => $survey->tenant_id,
                'total_number_question' => $totalQuestions,
                'question_number' => $answeredCount,
                'status' => $status,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $userProgress = DB::table('survey_user_progress')
            ->where('survey_id', $id)
            ->where('user_id', $userId)
            ->first();

        return view('fsrapp.survey.play', compact('survey', 'id', 'existingAnswers', 'userProgress'));
    }

    public function submit(Request $request, $id)
    {
        try {
            $survey = Survey::findOrFail($id);
            $user = Auth::user() ?? \App\Models\User::first();
            $userId = $user ? $user->id : null;
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

            // Check total questions and answered count
            $totalQuestions = $survey->questions()->count();
            $answeredQuestions = DB::table('survey_answers')->where([
                'survey_id' => $id,
                'user_id' => $userId
            ])->count();

            $isCompleted = ($answeredQuestions >= $totalQuestions && $totalQuestions > 0);
            $status = $isCompleted ? 'completed' : 'pending';

            // Update progress table
            DB::table('survey_user_progress')->updateOrInsert(
                [
                    'survey_id' => $id,
                    'user_id' => $userId,
                ],
                [
                    'tenant_id' => $tenantId,
                    'total_number_question' => $totalQuestions,
                    'question_number' => $answeredQuestions,
                    'status' => $status,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $rewardGiven = false;
            $rewardPoints = (int) ($survey->point ?? 0);
            if ($rewardPoints <= 0) $rewardPoints = 0;

             $rewardQuiz = (int) ($survey->quiz ?? 0);
            if ($rewardQuiz <= 0) $rewardQuiz = 0;

            if ($isCompleted && $userId) {
                $hasReward = DB::table('survey_rewards')->where([
                    'survey_id' => $id,
                    'user_id' => $userId
                ])->exists();

                if (!$hasReward) {
                    // 1. Record reward entry in survey_rewards ('settled' is valid enum value)
                    DB::table('survey_rewards')->insert([
                        'tenant_id' => $tenantId,
                        'survey_id' => $id,
                        'user_id' => $userId,
                        // 'reward_type' => 'point',
                        'reward_type' => $survey->reward_type,
                        'point' => $rewardPoints,
                        'quiz' => $rewardQuiz,
                        'status' => 'settled',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $rewardGiven = true;

                    if ($survey->reward_type === 'quiz') 
                    {
                        // 2. Update user's wallet_balance in users table
                        DB::table('users')->where('id', $userId)->increment('available_quiz_limit', $rewardQuiz);
                    } 

                    if ($survey->reward_type === 'point') 
                    {
                        // 2. Update user's wallet_balance in users table
                        DB::table('users')->where('id', $userId)->increment('wallet_balance', $rewardPoints);

                        // 3. Record transaction in wallet_history table
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

            return response()->json([
                'success' => true,
                'reward_given' => $rewardGiven,
                'points' => $rewardPoints,
                'completed' => $isCompleted,
                'question_number' => $answeredQuestions,
                'total_number_question' => $totalQuestions,
                'status' => $status
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Survey submit error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'completed' => false
            ], 500);
        }
    }
}
