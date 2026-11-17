<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\api\QuizRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuizController extends Controller
{
    protected QuizRepositoryInterface $quizRepository;

    public function __construct(QuizRepositoryInterface $quizRepository)
    {
        $this->quizRepository = $quizRepository;
    }

    /** GET /api/quiz/list */
    public function list(Request $request)
    {
        return response()->json($this->quizRepository->availableList($request->user()));
    }

    /** GET /api/quiz/upcoming */
    public function upcoming(Request $request)
    {
        return response()->json($this->quizRepository->upcomingList($request->user()));
    }

    /** GET /api/quiz/practice */
    public function practice(Request $request)
    {
        return response()->json($this->quizRepository->practiceList($request->user()));
    }

    /** GET /api/quiz/{quiz_id} */
    public function detail(Request $request, int $quizId)
    {
        return response()->json($this->quizRepository->detail($request->user(), $quizId));
    }

    /** POST /api/quiz/{quiz_id}/join */
    public function join(Request $request, int $quizId)
    {
        return response()->json(
            $this->quizRepository->join($request->user(), $quizId, $request->input('password'))
        );
    }

    /** GET /api/quiz/{quiz_id}/start */
    public function start(Request $request, int $quizId)
    {
        return response()->json($this->quizRepository->start($request->user(), $quizId));
    }

    
    public function getQuestion(Request $request, int $quizId)
    {
        return response()->json(
            $this->quizRepository->getQuestion($request->user(), $quizId, $request->all())
        );
    }

    public function getQuestionOptions(Request $request, int $quizId)
    {
        return response()->json(
            $this->quizRepository->getQuestionOptions($request->user(), $quizId, $request->all())
        );
    }

    /** POST /api/quiz/{quiz_id}/submit-answer */
    public function submitAnswer(Request $request, int $quizId)
    {
        return response()->json(
            $this->quizRepository->submitAnswer($request->user(), $quizId, $request->all())
        );
    }

    /** POST /api/quiz/{quiz_id}/submit */
    public function submitBulk(Request $request, int $quizId)
    {
        $validator = Validator::make($request->all(), [
            'answers'               => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer',
            'answers.*.answer'      => 'required|string',
            'answers.*.total_time'  => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json(
            $this->quizRepository->submitBulk($request->user(), $quizId, $request->input('answers'))
        );
    }

    /** GET /api/quiz/{quiz_id}/my-result */
    public function myResult(Request $request, int $quizId)
    {
        return response()->json($this->quizRepository->myResult($request->user(), $quizId));
    }

    /** GET /api/quiz/{quiz_id}/winners */
    public function winners(int $quizId)
    {
        return response()->json($this->quizRepository->winners($quizId));
    }

    /** GET /api/user/quiz-history */
    public function myHistory(Request $request)
    {
        return response()->json($this->quizRepository->myHistory($request->user()));
    }

    /** POST /api/quiz/{quiz_id}/leave */
    public function leave(Request $request, int $quizId)
    {
        return response()->json($this->quizRepository->leave($request->user(), $quizId));
    }

    public function answerSheet(Request $request, int $quizId)
    {
        return response()->json($this->quizRepository->answerSheet($request->user(), $quizId));
    }

    /** POST /api/quiz/{quiz_id}/use-lifeline */
    public function useLifeline(Request $request, int $quizId)
    {
        return response()->json($this->quizRepository->useLifeline($request->user(), $quizId, $request->all()));
    }

    /** POST /api/quiz/{quiz_id}/process-result */
    public function processResult(Request $request, int $quizId)
    {
        $this->quizRepository->quizResultAsync($quizId);
        return response()->json([
            'success' => true,
            'message' => 'Quiz result calculation dispatched in background.',
        ]);
    }

    
    /******************************* END *******************************/ 
}
