<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\api\QuizAdminRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuizAdminController extends Controller
{
    protected QuizAdminRepositoryInterface $quizAdminRepository;

    public function __construct(QuizAdminRepositoryInterface $quizAdminRepository)
    {
        $this->quizAdminRepository = $quizAdminRepository;
    }

    /** GET /api/admin/quiz/list */
    public function list(Request $request)
    {
        return response()->json(
            $this->quizAdminRepository->list($request->only(['status', 'category_id']))
        );
    }

    /** GET /api/admin/quiz/{quiz_id} */
    public function detail(int $quizId)
    {
        return response()->json($this->quizAdminRepository->detail($quizId));
    }

    /** POST /api/admin/quiz/create */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'                    => 'required|string|max:191',
            'category_id'              => 'nullable|integer',
            'sub_category_id'          => 'nullable|integer',
            'level_id'                 => 'nullable|integer',
            'type'                     => 'nullable|integer',
            'visibility'               => 'nullable|in:public,private',
            'password'                 => 'nullable|string|max:191',
            'retake_allowed'           => 'nullable|boolean',
            'max_attempts'             => 'nullable|integer',
            'pass_percentage'          => 'nullable|integer|min:0|max:100',
            'time_limit_minutes'       => 'nullable|integer',
            'per_que_time_limit_sec'   => 'nullable|integer',
            'question_limit'           => 'nullable|integer',
            'no_of_players'            => 'nullable|integer',
            'total_reward_amount'      => 'nullable|numeric',
            'negative_marking'         => 'nullable|boolean',
            'fifty_fifty'              => 'nullable|boolean',
            'switch_option'            => 'nullable|boolean',
            'availability_start'       => 'nullable|date',
            'availability_end'         => 'nullable|date',
            'scheduled_at'             => 'nullable|date',
            'question_ids'             => 'nullable|array',
            'question_ids.*'           => 'integer',
            'rank_rewards'             => 'nullable|array',
            'rank_rewards.*.rank_range'=> 'required_with:rank_rewards|string',
            'rank_rewards.*.type'      => 'required_with:rank_rewards|in:point,coupon',
            'rank_rewards.*.reward'    => 'nullable|numeric',
            'filters'                  => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json($this->quizAdminRepository->create($validator->validated()));
    }

    /** POST /api/admin/quiz/update/{quiz_id} */
    public function update(Request $request, int $quizId)
    {
        return response()->json($this->quizAdminRepository->update($quizId, $request->all()));
    }

    /** DELETE /api/admin/quiz/delete/{quiz_id} */
    public function delete(int $quizId)
    {
        return response()->json($this->quizAdminRepository->delete($quizId));
    }

    /** POST /api/admin/quiz/{quiz_id}/questions */
    public function setQuestions(Request $request, int $quizId)
    {
        $validator = Validator::make($request->all(), [
            'question_ids'   => 'required|array',
            'question_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json(
            $this->quizAdminRepository->setQuestions($quizId, $request->input('question_ids'))
        );
    }

    /** DELETE /api/admin/quiz/{quiz_id}/questions/{question_id} */
    public function removeQuestion(int $quizId, int $questionId)
    {
        return response()->json($this->quizAdminRepository->removeQuestion($quizId, $questionId));
    }

    /** POST /api/admin/quiz/{quiz_id}/rank-rewards */
    public function setRankRewards(Request $request, int $quizId)
    {
        $validator = Validator::make($request->all(), [
            'rank_range' => 'required|string|max:191',
            'type'       => 'required|in:point,coupon',
            'reward'     => 'nullable|numeric',
            'coupon_id'  => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json(
            $this->quizAdminRepository->setRankRewards($quizId, $validator->validated())
        );
    }

    /** POST /api/admin/quiz/{quiz_id}/filters */
    public function setFilters(Request $request, int $quizId)
    {
        return response()->json(
            $this->quizAdminRepository->setFilters($quizId, $request->all())
        );
    }

    /** POST /api/admin/quiz/{quiz_id}/publish */
    public function publish(Request $request, int $quizId)
    {
        return response()->json(
            $this->quizAdminRepository->publish($quizId, $request->all())
        );
    }

    /** POST /api/admin/quiz/{quiz_id}/approve */
    public function approve(Request $request, int $quizId)
    {
        $validator = Validator::make($request->all(), [
            'approve_status' => 'required|in:pending,approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json(
            $this->quizAdminRepository->approve($quizId, $request->input('approve_status'))
        );
    }

    /** GET /api/admin/quiz/{quiz_id}/status */
    public function stats(int $quizId)
    {
        return response()->json($this->quizAdminRepository->stats($quizId));
    }
}
