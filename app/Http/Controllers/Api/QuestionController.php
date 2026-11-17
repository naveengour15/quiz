<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\api\QuestionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuestionController extends Controller
{
    protected QuestionRepositoryInterface $questionRepository;

    public function __construct(QuestionRepositoryInterface $questionRepository)
    {
        $this->questionRepository = $questionRepository;
    }

    /** GET /api/admin/question/list */
    public function list(Request $request)
    {
        return response()->json(
            $this->questionRepository->list($request->only(['category_id', 'difficulty', 'status', 'moderation_status']))
        );
    }

    /** POST /api/admin/question/create */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_text'      => 'required|string',
            'type'                => 'required|in:mcq,true_false,image,audio,video,puzzle,fastest_finger_first,image_audio',
            'media_url'           => 'nullable|string|max:191',
            'options'             => 'nullable|array',
            'correct_answer'      => 'nullable|string|max:191',
            'explanation'         => 'nullable|string',
            'category_id'         => 'nullable|integer',
            'sub_category'        => 'nullable|string|max:191',
            'difficulty'          => 'nullable|in:easy,medium,hard,adaptive',
            'language'            => 'nullable|string|max:30',
            'time_limit_seconds'  => 'nullable|integer',
            'xp_value'            => 'nullable|integer',
            'negative_marking'    => 'nullable|numeric',
            'tags'                => 'nullable|string|max:191',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json($this->questionRepository->create($validator->validated()));
    }

    /** POST /api/admin/question/update/{id} */
    public function update(Request $request, int $id)
    {
        return response()->json($this->questionRepository->update($id, $request->all()));
    }

    /** DELETE /api/admin/question/delete/{id} */
    public function delete(int $id)
    {
        return response()->json($this->questionRepository->delete($id));
    }

    /** POST /api/admin/question/{id}/moderate */
    public function moderate(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'moderation_status' => 'required|in:pending,approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json(
            $this->questionRepository->moderate($id, $request->input('moderation_status'))
        );
    }
}
