<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PracticeQuiz;
use App\Models\Quiz;
use App\Models\VoucherHistory;
use App\Models\PracticeQuizQuestion;
 use Illuminate\Support\Facades\DB;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Repositories\admin\QuizRepositoryInterface;
use App\Interfaces\ActionTrailRepositoryInterface;

class PracticeQuizController extends Controller
{
    public function __construct(
        protected QuizRepositoryInterface $quizRepository,
        protected ActionTrailRepositoryInterface $actionTrail
    ) {
    }
    
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        
        $quizzes = PracticeQuiz::where('tenant_id', getSuperAdminTenantId())
            ->with([
                'category',
                'level',
                'quizType',
                'quizStatus',
                'practiceQuizQuestions' // ✅ FIXED: questions ki jagah practiceQuizQuestions
            ])
            ->withCount([
                'practiceQuizQuestions as questions_count', 
            
                'practiceQuizUsers as users_count', // Optional: Agar users count bhi chahiye to
                'adViewHistory as ad_views_count',
                'adClickHistory as ad_clicks_count'
            ])
            ->when($request->search, fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->visibility, fn ($q) => $q->where('visibility', $request->visibility))
            ->orderBy('scheduled_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('superadmin.practice-quizzes.index', compact('quizzes'));
    }

    /**
     * Refresh completed quizzes into practice_quizzes table
     */
    public function refreshCompletedQuizzes()
    {
        try {
            // 1. Pehle se मौजूद quiz_ids nikal lo
            $existingPracticeQuizIds = PracticeQuiz::pluck('quiz_id')->toArray();

            // 2. Completed quizzes nikalo, scheduled_at ke ascending order mein
            $completedQuizzes = Quiz::where('complete_status', 'completed') 
                ->whereNotIn('id', $existingPracticeQuizIds)
                ->where('visibility', '!=', 'fastest_finger_first')
                ->orderBy('scheduled_at', 'asc') 
                ->with('quizQuestions') // Original quiz ke questions load karo
                ->get();

            if ($completedQuizzes->isEmpty()) {
                return redirect()->back()->with('info', 'No new completed quizzes found to refresh into Practice Quizzes.');
            }

            // 3. Current maximum number nikalo "Practice Quiz - N" format ke titles mein
            $existingPracticeTitles = PracticeQuiz::where('title', 'like', 'Practice Quiz - %')->pluck('title')->toArray();
            $maxNumber = 0;
            foreach ($existingPracticeTitles as $title) {
                $number = (int) filter_var($title, FILTER_SANITIZE_NUMBER_INT);
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }

            $insertCount = 0;
            $currentNumber = $maxNumber;

            // 4. Loop chalakar Quiz aur uske Questions dono copy karo
            foreach ($completedQuizzes as $quiz) {
                $currentNumber++; 
                $newTitle = "Practice Quiz - {$currentNumber}";
                
                // A. Practice Quiz Create karo
                $practiceQuiz = PracticeQuiz::create([
                    'quiz_id'                => $quiz->id,
                    'tenant_id'              => $quiz->tenant_id,
                    'title'                  => $newTitle, 
                    'category_id'            => $quiz->category_id,
                    'sub_category_id'        => $quiz->sub_category_id,
                    'level_id'               => $quiz->level_id,
                    'type'                   => $quiz->type,
                    'visibility'             => $quiz->visibility,
                    'password'               => $quiz->password,
                    'retake_allowed'         => $quiz->retake_allowed,
                    'max_attempts'           => $quiz->max_attempts,
                    'pass_percentage'        => $quiz->pass_percentage,
                    'certificate_on_pass'    => $quiz->certificate_on_pass,
                    'no_of_points'           => $quiz->no_of_points,
                    'availability_start'     => $quiz->availability_start,
                    'availability_end'       => $quiz->availability_end,
                    'question_limit'         => $quiz->question_limit,
                    'no_of_players'          => $quiz->no_of_players,
                    'total_reward_amount'    => $quiz->total_reward_amount,
                    'negative_marking'       => $quiz->negative_marking,
                    'approve_status'         => $quiz->approve_status,
                    'notes'                  => $quiz->notes,
                    'creator_id'             => $quiz->creator_id,
                    'publisher_id'           => $quiz->publisher_id,
                    'scheduler_id'           => $quiz->scheduler_id,
                    'archivor_id'            => $quiz->archivor_id,
                    'status'                 => $quiz->status,
                    'complete_status'        => $quiz->complete_status,
                    'scheduled_at'           => $quiz->scheduled_at,
                    'per_que_time_limit_sec' => $quiz->per_que_time_limit_sec,
                    'login_time_period'      => $quiz->login_time_period,
                    'before_minute_entry'    => $quiz->before_minute_entry,
                    'time_limit_minutes'     => $quiz->time_limit_minutes,
                    'fifty_fifty'            => $quiz->fifty_fifty,
                    'switch_option'          => $quiz->switch_option,
                ]);

                // B. ✅ Questions Copy Karne ka Logic
                if ($quiz->quizQuestions && $quiz->quizQuestions->count() > 0) {
                    $questionsToInsert = [];
                    
                    foreach ($quiz->quizQuestions as $qq) {
                        $questionsToInsert[] = [
                            'tenant_id'            => $practiceQuiz->tenant_id,
                            'practice_quiz_id'     => $practiceQuiz->id, 
                            'question_id'          => $qq->question_id,
                            'order'                => $qq->order,
                            'is_switch_question'   => $qq->is_switch_question ? 1 : 0, // Boolean ko 1/0 mein convert kiya bulk insert ke liye
                            'bot_correct_answer'   => $qq->bot_correct_answer,
                            'question_text'        => $qq->question_text,
                            'type'                 => $qq->type,
                            // ✅ Safely handle JSON/Array for bulk insert
                            'options'              => is_array($qq->options) ? json_encode($qq->options) : $qq->options,
                            'correct_answer'       => $qq->correct_answer,
                            'explanation'          => $qq->explanation,
                            'created_at'           => now(),
                            'updated_at'           => now(),
                        ];
                    }

                    // Bulk Insert (Performance ke liye best)
                    if (!empty($questionsToInsert)) {
                        PracticeQuizQuestion::insert($questionsToInsert);
                    }
                }
                
                $insertCount++;
            }

            return redirect()->back()->with('success', "Successfully refreshed {$insertCount} completed quizzes along with their questions into Practice Quizzes.");

        } catch (\Exception $e) {
            Log::error('Practice Quiz Refresh Failed', [
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Failed to refresh practice quizzes. Please check server logs.');
        }
    }


    // ************show quiz******************
        // ************show practice quiz details******************
    public function showAll(PracticeQuiz $practiceQuiz) // ✅ FIX 1: Quiz ki jagah PracticeQuiz use karein
    {
        try {
            // ✅ FIX 2: PracticeQuiz ke valid relationships hi load karein
            $practiceQuiz->load([
                'category',
                'level',
                'quizType',
                'practiceQuizQuestions', // ✅ Questions ke liye sahi relationship
            ]);

            // Format data exactly as expected by the JavaScript frontend
            $data = [
                'title'                   => $practiceQuiz->title,
                'category'                => $practiceQuiz->category ? ['name' => $practiceQuiz->category->name] : null,
                'sub_category'            => null, // PracticeQuiz model mein subCategory relation nahi hai
                'level'                   => $practiceQuiz->level ? ['name' => $practiceQuiz->level->name] : null,
                'quiz_type'               => $practiceQuiz->quizType ? ['name' => $practiceQuiz->quizType->name] : null,
                'visibility'              => $practiceQuiz->visibility,
                'certificate_on_pass'     => $practiceQuiz->certificate_on_pass,
                'question_limit'          => $practiceQuiz->question_limit,
                'per_que_time_limit_sec'  => $practiceQuiz->per_que_time_limit_sec,
                'no_of_players'           => $practiceQuiz->no_of_players,
                'negative_marking'        => $practiceQuiz->negative_marking,
                'status'                  => $practiceQuiz->status,
                'scheduled_at'            => $practiceQuiz->scheduled_at ? $practiceQuiz->scheduled_at->toDateTimeString() : null,
                'availability_start'      => $practiceQuiz->availability_start ? $practiceQuiz->availability_start->toDateTimeString() : null,
                'availability_end'        => $practiceQuiz->availability_end ? $practiceQuiz->availability_end->toDateTimeString() : null,
                'before_minute_entry'     => $practiceQuiz->before_minute_entry,
                'fifty_fifty'             => $practiceQuiz->fifty_fifty,
                'switch_option'           => $practiceQuiz->switch_option,

                // ✅ FIX 3: practiceQuizQuestions se data map karein
                'questions' => $practiceQuiz->practiceQuizQuestions->map(function ($q) {
                    return [
                        'question_text'  => $q->question_text ?? 'N/A',
                        'options'        => $q->options, // Agar DB mein JSON string hai to frontend parse karega, ya yahan json_decode($q->options, true) kar sakte hain
                        'correct_answer' => $q->correct_answer ?? 'N/A',
                        'pivot'          => [
                            'order'              => $q->order ?? null,
                            'bot_correct_answer' => $q->bot_correct_answer ?? null,
                        ]
                    ];
                }),

                // Note: PracticeQuiz model mein rankRewards aur filter relationships define nahi hain.
                // Agar aapko wo chahiye, toh pehle PracticeQuiz model mein wo relations add karein.
                // Filhal unhe null ya empty array rakha hai taaki error na aaye.
                'rank_rewards' => [], 
                'filter'       => null,
            ];

            return response()->json([
                'success' => true,
                'data'    => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Practice Quiz Show Modal Error: ' . $e->getMessage(), [
                'practice_quiz_id' => $practiceQuiz->id ?? 'unknown',
                'trace'            => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch practice quiz details. ' . $e->getMessage()
            ], 500);
        }
    }
    // **************practice quiz joined users start*******

 
    public function joinedUsers(Request $request, $id)
    {
        $perPage = $request->get('per_page', 20);
        try {
            $practiceQuiz = \App\Models\PracticeQuiz::where('tenant_id', getSuperAdminTenantId())
                ->where('id', $id)
                ->firstOrFail();

            // Base Query with Counts
            $query = $practiceQuiz->practiceQuizUsers()
                ->with('user:id,name,mobile,profile_photo,created_at')
                ->select('practice_quiz_users.*')
                ->selectRaw('(SELECT COUNT(*) FROM practice_quiz_user_answers WHERE quiz_id = ? AND user_id = practice_quiz_users.user_id) as total_attempts', [$practiceQuiz->id])
                ->selectRaw('(SELECT COUNT(*) FROM practice_quiz_user_answers WHERE quiz_id = ? AND user_id = practice_quiz_users.user_id AND status = 1) as correct_attempts', [$practiceQuiz->id]);

            // ✅ Search Filter Logic (Name or Mobile)
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('mobile', 'like', "%{$search}%");
                });
            }

            // ✅ Pagination with Query String (taaki search filter page change hone par na hate)
            $joinedUsers = $query->orderBy('practice_quiz_users.created_at', 'desc')
                ->paginate($request->get('per_page', 20))
                ->withQueryString();

            return view('superadmin.practice-quizzes.joined-users', compact('practiceQuiz', 'joinedUsers'));
            
        } catch (\Exception $e) {
            Log::error('Practice Quiz Joined Users Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load joined users.');
        }
    }

    // ✅ UPDATED: AJAX ke liye JSON response return karega
    public function viewUserAnswers($quiz_id, $user_id)
    {
        try {
            // Fetch answers joined with questions table
            $answers = DB::table('practice_quiz_user_answers as ans')
                ->join('practice_quiz_question as q', 'ans.question_id', '=', 'q.id')
                ->where('ans.quiz_id', $quiz_id)
                ->where('ans.user_id', $user_id)
                ->select(
                    'q.question_text',
                    'ans.answer as user_answer',
                    'q.correct_answer',      // Correct answer bhi fetch karein
                    'ans.status',            // 1 = Correct, 0 = Incorrect
                    'ans.total_time'
                )
                ->orderBy('ans.id', 'asc')
                ->get();
 
            return response()->json([
                'success' => true,
                'data' => $answers
            ]);

        } catch (\Exception $e) {
            Log::error('View User Answers Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load answer sheet.'
            ], 500);
        }
    }
    // **************practice quiz joined users end*******
}