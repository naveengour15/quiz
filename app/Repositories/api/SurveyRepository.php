<?php

namespace App\Repositories\api;

use App\Interfaces\api\SurveyRepositoryInterface;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyReward;
use Illuminate\Support\Facades\DB;

class SurveyRepository implements SurveyRepositoryInterface
{
    // ---------------------------------------------------------------
    // Admin
    // ---------------------------------------------------------------
    public function createQuestion(array $data): array
    {
        $question = SurveyQuestion::create($data);

        return ['success' => true, 'message' => 'Survey question created', 'question' => $question];
    }

    public function updateQuestion(int $id, array $data): array
    {
        $question = SurveyQuestion::find($id);

        if (! $question) {
            return ['success' => false, 'message' => 'Survey question not found'];
        }

        $question->fill($data);
        $question->save();

        return ['success' => true, 'message' => 'Survey question updated', 'question' => $question];
    }

    public function deleteQuestion(int $id): array
    {
        $question = SurveyQuestion::find($id);

        if (! $question) {
            return ['success' => false, 'message' => 'Survey question not found'];
        }

        $question->delete();

        return ['success' => true, 'message' => 'Survey question deleted'];
    }

    public function questionsList(int $surveyId): array
    {
        $questions = SurveyQuestion::where('survey_id', $surveyId)
            ->where('status', 'active')
            ->get();

        return ['success' => true, 'data' => $questions];
    }

    public function setRewardRule(int $surveyId, array $data): array
    {
        // Stored as a template row (user_id null) that the play/submit flow copies per-user.
        $reward = SurveyReward::updateOrCreate(
            ['survey_id' => $surveyId, 'user_id' => null],
            $data
        );

        return ['success' => true, 'message' => 'Survey reward rule saved', 'reward' => $reward];
    }

    // ---------------------------------------------------------------
    // User
    // ---------------------------------------------------------------


    
    public function surveyList()
    {
        $survey = Survey::where('status', 'active')->get();
        
        return ['success' => true, 'survey' => $survey];
    }


    public function startSurvey(int $surveyId): array
    {
        $questions = SurveyQuestion::where('survey_id', $surveyId)
            ->where('status', 'active')
            ->get(['id as question_id', 'question', 'yes', 'no']);

        return ['success' => true, 'survey_id' => $surveyId, 'questions' => $questions];
    }

    public function submitAnswer($user, int $surveyId, array $data): array
    {
        $answer = SurveyAnswer::updateOrCreate(
            [
                'survey_id'   => $surveyId,
                'user_id'     => $user->id,
                'question_id' => $data['question_id'],
            ],
            [
                'tenant_id' => $user->tenant_id ?? 1,
                'answer'    => $data['answer'],
            ]
        );

        return ['success' => true, 'message' => 'Answer submitted', 'answer' => $answer];
    }

    public function submitBulk($user, int $surveyId, array $answers): array
    {
        return DB::transaction(function () use ($user, $surveyId, $answers) {
            foreach ($answers as $ans) {
                SurveyAnswer::updateOrCreate(
                    [
                        'survey_id'   => $surveyId,
                        'user_id'     => $user->id,
                        'question_id' => $ans['question_id'],
                    ],
                    [
                        'tenant_id' => $user->tenant_id ?? 1,
                        'answer'    => $ans['answer'],
                    ]
                );
            }

            // Grant reward based on the reward-rule template set by admin.
            $rewardRule = SurveyReward::where('survey_id', $surveyId)->whereNull('user_id')->first();

            $userReward = null;
            if ($rewardRule) {
                $userReward = SurveyReward::updateOrCreate(
                    ['survey_id' => $surveyId, 'user_id' => $user->id],
                    [
                        'tenant_id'   => $user->tenant_id ?? 1,
                        'reward_type' => $rewardRule->reward_type,
                        'point'       => $rewardRule->point,
                        'status'      => 'settled',
                    ]
                );

                $user->increment('wallet_balance', $rewardRule->point);
            }

            return [
                'success' => true,
                'message' => 'Survey submitted successfully',
                'reward'  => $userReward,
            ];
        });
    }

    public function myResult($user, int $surveyId): array
    {
        $reward = SurveyReward::where('survey_id', $surveyId)->where('user_id', $user->id)->first();

        return ['success' => true, 'reward' => $reward];
    }

    public function myHistory($user): array
    {
        $history = SurveyReward::where('user_id', $user->id)->latest('id')->paginate(20);

        return ['success' => true, 'data' => $history];
    }
}
