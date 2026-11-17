<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\api\SurveyRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SurveyController extends Controller
{
    protected SurveyRepositoryInterface $surveyRepository;

    public function __construct(SurveyRepositoryInterface $surveyRepository)
    {
        $this->surveyRepository = $surveyRepository;
    }

    // ---------------------------------------------------------------
    // Admin
    // ---------------------------------------------------------------

    /** POST /api/admin/survey/question/create */
    public function createQuestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'survey_id'   => 'required|integer',
            'question'    => 'required|string',
            'yes'         => 'nullable|string|max:191',
            'no'          => 'nullable|string|max:191',
            'correct_ans' => 'required|in:yes,no',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json($this->surveyRepository->createQuestion($validator->validated()));
    }

    /** POST /api/admin/survey/question/update/{id} */
    public function updateQuestion(Request $request, int $id)
    {
        return response()->json($this->surveyRepository->updateQuestion($id, $request->all()));
    }

    /** DELETE /api/admin/survey/question/delete/{id} */
    public function deleteQuestion(int $id)
    {
        return response()->json($this->surveyRepository->deleteQuestion($id));
    }

    /** GET /api/admin/survey/{survey_id}/questions */
    public function questionsList(int $surveyId)
    {
        return response()->json($this->surveyRepository->questionsList($surveyId));
    }

    /** POST /api/admin/survey/{survey_id}/reward-setup */
    public function setRewardRule(Request $request, int $surveyId)
    {
        $validator = Validator::make($request->all(), [
            'reward_type' => 'required|string|max:191',
            'point'       => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json(
            $this->surveyRepository->setRewardRule($surveyId, $validator->validated())
        );
    }

    // ---------------------------------------------------------------
    // User
    // ---------------------------------------------------------------

    
    public function surveyList()
    {
        return response()->json($this->surveyRepository->surveyList());
    }

    /** GET /api/survey/{survey_id}/start */
    public function start(int $surveyId)
    {
        return response()->json($this->surveyRepository->startSurvey($surveyId));
    }

    /** POST /api/survey/{survey_id}/submit-answer */
    public function submitAnswer(Request $request, int $surveyId)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|integer',
            'answer'      => 'required|in:yes,no',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json(
            $this->surveyRepository->submitAnswer($request->user(), $surveyId, $validator->validated())
        );
    }

    /** POST /api/survey/{survey_id}/submit */
    public function submitBulk(Request $request, int $surveyId)
    {
        $validator = Validator::make($request->all(), [
            'answers'               => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer',
            'answers.*.answer'      => 'required|in:yes,no',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json(
            $this->surveyRepository->submitBulk($request->user(), $surveyId, $request->input('answers'))
        );
    }

    /** GET /api/survey/{survey_id}/my-result */
    public function myResult(Request $request, int $surveyId)
    {
        return response()->json($this->surveyRepository->myResult($request->user(), $surveyId));
    }

    /** GET /api/user/survey-history */
    public function myHistory(Request $request)
    {
        return response()->json($this->surveyRepository->myHistory($request->user()));
    }
}
