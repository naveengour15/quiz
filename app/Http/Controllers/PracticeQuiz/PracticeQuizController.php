<?php

namespace App\Http\Controllers\PracticeQuiz;

use App\Http\Controllers\Controller;
use App\Repositories\PracticeQuiz\PracticeQuizRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Banner;

class PracticeQuizController extends Controller
{
    protected PracticeQuizRepositoryInterface $practiceRepository;

    public function __construct(PracticeQuizRepositoryInterface $practiceRepository)
    {
        $this->practiceRepository = $practiceRepository;
    }

    protected function getUser(Request $request)
    {
        return $request->user() ?? \Illuminate\Support\Facades\Auth::user() ?? \App\Models\User::first();
    }

    /** GET /practice-quiz/list */
    public function list(Request $request)
    {
        return response()->json($this->practiceRepository->availableList($this->getUser($request)));
    }

    /** GET /practice-quiz/{quiz_id}/detail */
    public function detail(Request $request, int $quizId)
    {
        return response()->json($this->practiceRepository->detail($this->getUser($request), $quizId));
    }

    /** GET /practice-quiz/{quiz_id}/start */
    public function start(Request $request, int $quizId)
    {
        return response()->json($this->practiceRepository->start($this->getUser($request), $quizId));
    }

    /** POST /practice-quiz/{quiz_id}/get-question */
    public function getQuestion(Request $request, int $quizId)
    {
        return response()->json($this->practiceRepository->getQuestion($this->getUser($request), $quizId, $request->all()));
    }

    /** POST /practice-quiz/{quiz_id}/use-lifeline */
    public function useLifeline(Request $request, int $quizId)
    {
        $validator = Validator::make($request->all(), [
            'lifeline'    => 'required|string|in:fifty_fifty,switch_option',
            'question_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json($this->practiceRepository->useLifeline(
            $this->getUser($request),
            $quizId,
            $request->input('lifeline'),
            (int)$request->input('question_id')
        ));
    }

    /** POST /practice-quiz/{quiz_id}/submit-answer */
    public function submitAnswer(Request $request, int $quizId)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|integer',
            'answer'      => 'nullable|string',
            'total_time'  => 'nullable|numeric',
            'show_time'   => 'nullable|string',
            'ans_time'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json($this->practiceRepository->submitAnswer($this->getUser($request), $quizId, $request->all()));
    }

    /** GET /practice-quiz/{quiz_id}/my-result */
    public function myResult(Request $request, int $quizId)
    {
        return response()->json($this->practiceRepository->myResult($this->getUser($request), $quizId));
    }

    /** GET /practice-quiz/{quiz_id}/answer-sheet */
    public function answerSheet(Request $request, int $quizId)
    {
        return response()->json($this->practiceRepository->answerSheet($this->getUser($request), $quizId));
    }

    /** Web Views for Practice Quiz */
    public function index(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user() ?? \App\Models\User::first();
        $tenantId = session('tenant_id', 1);
        $banners = Banner::where('status', 'active')->latest()->get();
        $practiceQuizzes = \App\Models\PracticeQuiz::with(['quizType'])
            ->whereIn('tenant_id', [$tenantId, 1])
            ->where('approve_status', 'approved')
            ->orderBy('id', 'asc')
            ->get();

        if ($practiceQuizzes->isEmpty()) {
            $practiceQuizzes = \App\Models\Quiz::with(['quizType'])
                ->whereIn('tenant_id', [$tenantId, 1])
                ->where('type', 'Practice')
                ->where('approve_status', 'approved')
                ->orderBy('id', 'asc')
                ->get();
        }

        $completedQuizIds = [];
        if ($user) {
            $completedQuizIds = \App\Models\PracticeQuizUser::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->where('status', 'completed')
                        ->orWhere('total_correct_answer', '>', 0)
                        ->orWhere('total_time', '>', 0);
                })
                ->pluck('quiz_id')
                ->toArray();

            $answeredQuizIds = \App\Models\PracticeQuizUserAnswer::where('user_id', $user->id)
                ->pluck('quiz_id')
                ->toArray();

            $completedQuizIds = array_unique(array_merge($completedQuizIds, $answeredQuizIds));
        }

        // Attach serial, is_completed, and is_unlocked status
        $previousCompleted = true; // First quiz is always unlocked
        foreach ($practiceQuizzes as $index => $quiz) {
            $quiz->serial_number = $index + 1;
            $quiz->is_completed = in_array($quiz->id, $completedQuizIds);
            $quiz->is_unlocked = $previousCompleted;
            
            // Next quiz unlocked ONLY if current quiz is fully completed
            $previousCompleted = $quiz->is_completed;
        }

        $completedCount = count(array_intersect($practiceQuizzes->pluck('id')->toArray(), $completedQuizIds));

        $totalCount = $practiceQuizzes->count();
        $limit = (int) $request->input('limit', 10);
        $practiceQuizzes = $practiceQuizzes->take($limit);

        if (view()->exists('fsrapp.practice-quiz.index')) {
            return view('fsrapp.practice-quiz.index', compact('practiceQuizzes', 'user', 'completedCount', 'totalCount', 'limit','banners'));
        }
        return view('webapp.practice-quiz.index', compact('practiceQuizzes', 'user', 'completedCount', 'totalCount', 'limit','banners'));
    }

    public function playView(Request $request, $id)
    {
         $banners = Banner::where('status', 'active')->latest()->get();
        $user = \Illuminate\Support\Facades\Auth::user() ?? \App\Models\User::first();
        if ($user) {
            $hasPlayed = \App\Models\PracticeQuizUser::where('user_id', $user->id)
                ->where('quiz_id', $id)
                ->where(function ($q) {
                    $q->where('status', 'completed')
                        ->orWhere('total_correct_answer', '>', 0)
                        ->orWhere('total_time', '>', 0);
                })
                ->exists();

            if (!$hasPlayed) {
                $hasPlayed = \App\Models\PracticeQuizUserAnswer::where('user_id', $user->id)
                    ->where('quiz_id', $id)
                    ->exists();
            }

            if ($hasPlayed) {
                return redirect()->route('fsrapp.practice-quiz.review', ['id' => $id]);
            }
        }

        if (view()->exists('fsrapp.practice-quiz.play')) {
            return view('fsrapp.practice-quiz.play', ['id' => $id, 'banners' => $banners]);
        }
        return view('webapp.practice-quiz.play', ['id' => $id, 'banners' => $banners]);
    }

    public function reviewView(Request $request, $id)
    {
         $banners = Banner::where('status', 'active')->latest()->get();
        if (view()->exists('fsrapp.practice-quiz.review')) {
            return view('fsrapp.practice-quiz.review', ['id' => $id, 'banners' => $banners]);
        }
        return view('webapp.practice-quiz.review', ['id' => $id, 'banners' => $banners]);
    }

    public function resultView(Request $request, $id)
    {
        $user = Auth::user() ?? \App\Models\User::first();
        if ($user) {
            \App\Models\PracticeQuizUser::where('quiz_id', $id)
                ->where('user_id', $user->id)
                ->update(['status' => 'completed']);
        }

        if (view()->exists('fsrapp.practice-quiz.result')) {
            return view('fsrapp.practice-quiz.result', ['id' => $id]);
        }
        return view('webapp.practice-quiz.result', ['id' => $id]);
    }
}
