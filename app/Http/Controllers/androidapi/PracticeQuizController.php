<?php

namespace App\Http\Controllers\androidapi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\androidapi\Traits\ResolvesAndroidUser;
use App\Repositories\PracticeQuiz\PracticeQuizRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PracticeQuizController extends Controller
{
    use ResolvesAndroidUser;

    public function __construct(private PracticeQuizRepositoryInterface $repository)
    {
    }

    private function user(Request $request)
    {
        return $this->getAuthUser($request);
    }

    public function list(Request $request)
    {
        return response()->json($this->repository->availableList($this->user($request)));
    }

    public function detail(Request $request, int $id)
    {
        return response()->json($this->repository->detail($this->user($request), $id));
    }

    public function start(Request $request, int $id)
    {
        return response()->json($this->repository->start($this->user($request), $id));
    }

    public function question(Request $request, int $id)
    {
        return response()->json($this->repository->getQuestion($this->user($request), $id, $request->all()));
    }

    public function submit(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|integer',
            'answer' => 'nullable|string',
            'total_time' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json($this->repository->submitAnswer($this->user($request), $id, $request->all()));
    }

    public function lifeline(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'lifeline' => 'required|string|in:fifty_fifty,switch_option',
            'question_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json($this->repository->useLifeline(
            $this->user($request),
            $id,
            $request->input('lifeline'),
            $request->integer('question_id')
        ));
    }

    public function result(Request $request, int $id)
    {
        return response()->json($this->repository->myResult($this->user($request), $id));
    }

    public function answerSheet(Request $request, int $id)
    {
        return response()->json($this->repository->answerSheet($this->user($request), $id));
    }
}
