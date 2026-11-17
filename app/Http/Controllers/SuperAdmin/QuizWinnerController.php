<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizWinner;
use App\Models\QuizUser;
use App\Models\LeaderPointHistory;
use App\Models\QuizQuestionUserAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class QuizWinnerController extends Controller
{
    // public function winnerList(Quiz $quiz)
    // {
    //     $winners = QuizWinner::with(['user', 'rankReward', 'rankReward.coupon'])
    //         ->where('quiz_id', $quiz->id)
    //         ->orderBy('rank', 'asc')
    //         ->paginate(20);

    //     return view('superadmin.quizzes.winners', compact('quiz', 'winners'));
    // }

    public function winnerList(Request $request, $quiz)
    {
        if (!$quiz instanceof Quiz) {
            $quiz = Quiz::resolveQuiz($quiz);
        }

        QuizWinner::recalculateRanks((int) $quiz->id);

        $perPage = $request->get('per_page', 10);
        $winners = QuizWinner::with([
            'user',
            'rankReward',
            'rankReward.coupon',
            'voucherHistory',
            'voucherHistory.voucher'
        ])->where('quiz_id', $quiz->id)
          ->where('total_question', '>=', 1)

         // Search Player
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {

                    // Rank search
                    $q->where('rank', 'like', "%{$search}%")

                        // User search
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%");
                        });
                });
            })

            // Status filter
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
        ->orderBy('rank', 'asc')
        ->paginate($perPage)
        ->withQueryString();

         // 🏆 TOP 3 WINNERS (Separate Query - Yeh pagination se affect nahi hoga)
        $topWinners = QuizWinner::with(['user', 'rankReward'])
            ->where('quiz_id', $quiz->id)
            ->where('total_question', '>=', 1)
            ->whereIn('rank', [1, 2, 3])
            ->get()
            ->keyBy('rank'); // Isse array rank ke hisaab se index ho jayega (e.g., $topWinners[1])

        // ✅ YAHAN HELPER FUNCTION CALL KAREIN
        $this->attachPointHistoryToWinners($winners, $quiz);    
        $this->attachPointHistoryToWinners($topWinners, $quiz);    

        return view('superadmin.quizzes.winners', compact('quiz', 'winners','topWinners'));
    }

    /**
     * Attach Leader Point History to Winners Collection
     */
    private function attachPointHistoryToWinners($winners, $quiz)
    {
        // 1. Agar visibility 'leaderboard' ya 'fastest_finger_first' NAHI hai, toh sabke liye 0 set kar do
        if (!in_array($quiz->visibility, ['leaderboard', 'fastest_finger_first'])) {
            foreach ($winners as $winner) {
                $winner->point_history = (object) [
                    'old_point' => 0,
                    'point' => 0,
                    'new_point' => 0,
                    'type' => 'none',
                    'created_at' => null,
                ];
            }
            return;
        }

        // 2. Agar 'leaderboard' ya 'fastest_finger_first' hai, toh database se fetch karo
        // Performance ke liye: current page ke sabhi user_ids ek sath fetch kar rahe hain (N+1 query avoid)
        $userIds = $winners->pluck('user_id')->filter()->toArray();

        if (empty($userIds)) {
            return;
        }

        // Database se pure row fetch karo aur user_id ke hisaab se map kar lo
        $histories = LeaderPointHistory::whereIn('user_id', $userIds)
            ->where('quiz_id', $quiz->id)
            ->get()
            ->keyBy('user_id'); 

        // 3. Har winner ke sath uska history attach kar do
        foreach ($winners as $winner) {
            $winner->point_history = $histories->get($winner->user_id) ?? (object) [
                'old_point' => 0,
                'point' => 0,
                'new_point' => 0,
                'type' => 'none',
                'created_at' => null,
            ];
        }
    }

    // NEW: AJAX method to fetch user's specific answers for a quiz
    // public function getUserAnswers_old($quizId, $userId)
    // {
    //     // 1. Request aane par log karein
    //     // Log::info('Fetching quiz answers via AJAX', [
    //     //     'quiz_id' => $quizId,
    //     //     'user_id' => $userId
    //     // ]);

    //     try {
    //         $answers = QuizQuestionUserAnswer::with('question')
    //             ->where('quiz_id', $quizId)
    //             ->where('user_id', $userId)
    //             ->orderBy('id', 'asc')
    //             ->get();

    //         // 2. Success par kitne records mile, ye log karein
    //         // Log::info('Answers fetched successfully', [
    //         //     'records_found' => $answers->count()
    //         // ]);

    //         $quizRepo = app(\App\Interfaces\api\QuizRepositoryInterface::class);

    //         $formattedData = $answers->map(function ($item) use ($quizRepo) {
    //             $question = $item->question;
    //             $opts = $question?->options;
    //             if (is_string($opts)) $opts = json_decode($opts, true) ?: [];
    //             if (!is_array($opts)) $opts = [];

    //             $hindiOpts = $question?->hindi_options;
    //             if (is_string($hindiOpts)) $hindiOpts = json_decode($hindiOpts, true) ?: [];
    //             if (!is_array($hindiOpts)) $hindiOpts = [];

    //             $cleanOptions = [];
    //             $maxOptCount = max(count($opts), count($hindiOpts));
    //             for ($optIdx = 0; $optIdx < $maxOptCount; $optIdx++) {
    //                 $opt = $opts[$optIdx] ?? '';
    //                 $optText = is_array($opt) ? ($opt['option_text'] ?? $opt['text'] ?? $opt['label'] ?? '') : (string) $opt;
    //                 $optText = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', $optText));

    //                 $hOpt = $hindiOpts[$optIdx] ?? '';
    //                 $hOptText = is_array($hOpt) ? ($hOpt['option_text'] ?? $hOpt['text'] ?? $hOpt['label'] ?? '') : (string) $hOpt;
    //                 $hOptText = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', $hOptText));

    //                 $hasH = ($hOptText !== '');
    //                 $hasE = ($optText !== '');

    //                 if ($hasH && $hasE && strcasecmp($optText, $hOptText) !== 0) {
    //                     $cleanOptions[] = $hOptText . ' (' . $optText . ')';
    //                 } elseif ($hasH) {
    //                     $cleanOptions[] = $hOptText;
    //                 } elseif ($hasE) {
    //                     $cleanOptions[] = $optText;
    //                 }
    //             }

    //             $rawCorrect = $question?->correct_answer ?: ($question?->hindi_correct_answer ?: ($question?->question?->correct_answer ?: ($question?->question?->hindi_correct_answer ?: $question?->bot_correct_answer)));

    //             $correctAns = '';
    //             if ($rawCorrect !== null && trim((string)$rawCorrect) !== '') {
    //                 $correctAns = $quizRepo->resolveMatchingOptionStr($rawCorrect, $cleanOptions ?: $opts);
    //                 if ($correctAns === '') {
    //                     $correctAns = (string)$rawCorrect;
    //                 }
    //             }

    //             $userAns = (string)($item->answer ?? '');
    //             if ($userAns !== '' && !empty($cleanOptions)) {
    //                 $resolvedUser = $quizRepo->resolveMatchingOptionStr($userAns, $cleanOptions);
    //                 if ($resolvedUser !== '') $userAns = $resolvedUser;
    //             }

    //             $qText = $question ? ($question->question_text ?? 'Question text missing') : 'Question deleted';
    //             if ($question && !empty($question->hindi_question)) {
    //                 $qText = $question->hindi_question . (!empty($question->question_text) ? ' (' . $question->question_text . ')' : '');
    //             }

    //             return [
    //                 'question_text'  => $qText,
    //                 'user_answer'    => $userAns ?: ($item->answer ?: 'Skipped'),
    //                 'correct_answer' => $correctAns ?: 'N/A',
    //                 'is_correct'     => (bool) $item->status,
    //                 'ans_time'       => $item->ans_time ?? 0,
    //                 'total_time'     => $item->total_time ?: 10,
    //             ];
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'data'    => $formattedData
    //         ]);

    //     } catch (\Exception $e) {
    //         // 3. Agar koi error aaye, toh poora detail ke sath error log karein
    //         Log::error('Failed to fetch quiz answers', [
    //             'quiz_id' => $quizId,
    //             'user_id' => $userId,
    //             'error_message' => $e->getMessage(),
    //             'file' => $e->getFile(),
    //             'line' => $e->getLine(),
    //             // 'trace' => $e->getTraceAsString() // Agar error na mile toh isko uncomment kar dein
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Server error: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    // NEW: AJAX method to fetch user's specific answers for a quiz
    public function getUserAnswers($quizId, $userId)
    {
        if (!is_numeric($quizId)) {
            $decrypted = Quiz::decryptId($quizId);
            if (!empty($decrypted) && is_numeric($decrypted)) {
                $quizId = (int) $decrypted;
            }
        }

        try {
            $answers = QuizQuestionUserAnswer::with('question')
                ->where('quiz_id', $quizId)
                ->where('user_id', $userId)
                ->orderBy('id', 'asc')
                ->get();

            $quizRepo = app(\App\Interfaces\api\QuizRepositoryInterface::class);

            $formattedData = $answers->map(function ($item) use ($quizRepo) {
                $question = $item->question;
                $opts = $question?->options;
                if (is_string($opts)) $opts = json_decode($opts, true) ?: [];
                if (!is_array($opts)) $opts = [];

                $hindiOpts = $question?->hindi_options;
                if (is_string($hindiOpts)) $hindiOpts = json_decode($hindiOpts, true) ?: [];
                if (!is_array($hindiOpts)) $hindiOpts = [];

                $cleanOptions = [];
                $maxOptCount = max(count($opts), count($hindiOpts));
                for ($optIdx = 0; $optIdx < $maxOptCount; $optIdx++) {
                    $opt = $opts[$optIdx] ?? '';
                    $optText = is_array($opt) ? ($opt['option_text'] ?? $opt['text'] ?? $opt['label'] ?? '') : (string) $opt;
                    $optText = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', $optText));

                    $hOpt = $hindiOpts[$optIdx] ?? '';
                    $hOptText = is_array($hOpt) ? ($hOpt['option_text'] ?? $hOpt['text'] ?? $hOpt['label'] ?? '') : (string) $hOpt;
                    $hOptText = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', $hOptText));

                    $hasH = ($hOptText !== '');
                    $hasE = ($optText !== '');

                    if ($hasH && $hasE && strcasecmp($optText, $hOptText) !== 0) {
                        $cleanOptions[] = $hOptText . ' (' . $optText . ')';
                    } elseif ($hasH) {
                        $cleanOptions[] = $hOptText;
                    } elseif ($hasE) {
                        $cleanOptions[] = $optText;
                    }
                }

                // ✅ FIX 1: ?: ki jagah ?? use kiya taaki '0' skip na ho
                $rawCorrect = $question?->correct_answer ?? 
                            $question?->hindi_correct_answer ?? 
                            $question?->question?->correct_answer ?? 
                            $question?->question?->hindi_correct_answer ?? 
                            $question?->bot_correct_answer ?? '';

                $correctAns = '';
                // ✅ FIX 2: Strict check taaki '0' valid maana jaye
                if ($rawCorrect !== null && trim((string)$rawCorrect) !== '') {
                    $correctAns = $quizRepo->resolveMatchingOptionStr((string)$rawCorrect, $cleanOptions ?: $opts);
                    if ($correctAns === '') {
                        $correctAns = (string)$rawCorrect;
                    }
                }

                // ✅ FIX 3: User answer ke liye strict check
                $userAnsRaw = $item->answer ?? '';
                $userAns = (string) $userAnsRaw;
                
                if ($userAns !== '' && !empty($cleanOptions)) {
                    $resolvedUser = $quizRepo->resolveMatchingOptionStr($userAns, $cleanOptions);
                    if ($resolvedUser !== '') {
                        $userAns = $resolvedUser;
                    }
                }

                // ✅ FIX 4: Final display me ?: ki jagah ternary with strict check use kiya
                $finalUserAns = (trim($userAns) !== '') ? $userAns : 'Skipped';
                $finalCorrectAns = (trim($correctAns) !== '') ? $correctAns : 'N/A';

                $qText = $question ? ($question->question_text ?? 'Question text missing') : 'Question deleted';
                if ($question && !empty($question->hindi_question)) {
                    $qText = $question->hindi_question . (!empty($question->question_text) ? ' (' . $question->question_text . ')' : '');
                }

                return [
                    'question_text'  => $qText,
                    'user_answer'    => $finalUserAns,      // ✅ Ab '0' show hoga, 'Skipped' nahi
                    'correct_answer' => $finalCorrectAns,   // ✅ Ab '0' show hoga, 'N/A' nahi
                    'is_correct'     => (bool) $item->status,
                    'ans_time'       => $item->ans_time ?? 0,
                    'total_time'     => $item->total_time ?? 10, // ?: ki jagah ?? use kiya
                ];
            });

            return response()->json([
                'success' => true,
                'data'    => $formattedData
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch quiz answers', [
                'quiz_id' => $quizId,
                'user_id' => $userId,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    // *************joined user quiz start**********
    public function joinedUsers(Request $request, $quiz)
    {
        if (!$quiz instanceof Quiz) {
            $quiz = Quiz::resolveQuiz($quiz);
        }

        $perPage = $request->get('per_page', 10);
        $activeTab = $request->get('tab', 'joined');

        if (\App\Services\RedisQuizService::isAvailable()) {
            try {
                do {
                    $drained = \App\Services\RedisQuizService::collectAndBulkUpsertPendingEnrollments(5000);
                } while ($drained >= 5000);
            } catch (\Throwable $e) {
                \Log::warning("SuperAdmin QuizWinnerController@joinedUsers Redis drain error: " . $e->getMessage());
            }
        }

        // Auto-heal: Agar kisi user ne quiz me answer submit kiya hai aur unka out_status 'out' nahi hai, to unhe 'in' mark karein
        try {
            QuizUser::where('quiz_id', $quiz->id)
                ->where('out_status', '!=', 'out')
                ->where(function ($query) {
                    $query->whereNull('out_status')->orWhere('out_status', '!=', 'in');
                })
                ->whereIn('user_id', function ($query) use ($quiz) {
                    $query->select('user_id')
                        ->from('quiz_question_user_answers')
                        ->where('quiz_id', $quiz->id)
                        ->whereNotNull('answer')
                        ->where('answer', '!=', '');
                })
                ->update(['out_status' => 'in', 'status' => 'active']);
        } catch (\Throwable $e) {
            \Log::warning("SuperAdmin QuizWinnerController auto-sync out_status error: " . $e->getMessage());
        }

        // ✅ 1. Total Joined Users Count (Sabhi users)
        $totalJoinedCount = QuizUser::where('quiz_id', $quiz->id)->count();

        // ✅ 2. Total Played Users Count (Jinka out_status 'in' hai)
        $totalPlayedCount = QuizUser::where('quiz_id', $quiz->id)
            ->where('out_status', 'in')
            ->count();

        // ✅ 3. Total Left Users Count (Jinka out_status 'out' hai)
        $totalLeftCount = QuizUser::where('quiz_id', $quiz->id)
            ->where('out_status', 'out')
            ->count();

        // ✅ 4. Total Waiting Users Count (Jinhone 0 answer submit kiya hai aur out nahi hue)
        $totalWaitingCount = QuizUser::where('quiz_id', $quiz->id)
            ->where(function ($query) {
                $query->whereNull('out_status')->orWhere('out_status', 'waiting');
            })
            ->count();

        // Search & Filter parameters for Joined Users
        $joinedSearch = ($activeTab === 'joined' && $request->filled('search')) ? $request->search : $request->get('joined_search');
        $joinedStatus = ($activeTab === 'joined' && $request->filled('status')) ? $request->status : $request->get('joined_status');

        // ✅ Main Table Data for Joined Users
        $joinedUsers = QuizUser::with([
                'user' => function ($query) {
                    $query->withCount('quizUsers');
                }
            ])
            ->withCount(['quizQuestionUserAnswers as answer_count' => function ($query) use ($quiz) {
                $query->where('quiz_id', $quiz->id);
            }])
            ->where('quiz_id', $quiz->id)
            ->when(!empty($joinedSearch), function ($query) use ($joinedSearch) {
                $query->whereHas('user', function ($q) use ($joinedSearch) {
                    $q->where('name', 'like', "%{$joinedSearch}%")
                    ->orWhere('mobile', 'like', "%{$joinedSearch}%")
                    ->orWhere('email', 'like', "%{$joinedSearch}%");
                });
            })
            ->when(!empty($joinedStatus), function ($query) use ($joinedStatus) {
                $query->where('status', $joinedStatus);
            })
            ->when($request->filled('fifty_fifty'), function ($query) use ($request) {
                $query->where('fifty_fifty', $request->fifty_fifty);
            })
            ->when($request->filled('switch_option'), function ($query) use ($request) {
                $query->where('switch_option', $request->switch_option);
            })
            ->latest('created_at')
            ->paginate($perPage, ['*'], 'joined_page')
            ->withQueryString();

        // ══════════════════════════════════════════════
        // 🏆 WINNERS DATA FOR TAB 2
        // ══════════════════════════════════════════════
        QuizWinner::recalculateRanks((int) $quiz->id);

        $winnerSearch = ($activeTab === 'winners' && $request->filled('search')) ? $request->search : $request->get('winner_search');
        $winnerStatus = ($activeTab === 'winners' && $request->filled('status')) ? $request->status : $request->get('winner_status');

        $winners = QuizWinner::with([
            'user',
            'rankReward',
            'rankReward.coupon',
            'voucherHistory',
            'voucherHistory.voucher'
        ])->where('quiz_id', $quiz->id)
            ->when(!empty($winnerSearch), function ($query) use ($winnerSearch) {
                $query->where(function ($q) use ($winnerSearch) {
                    $q->where('rank', 'like', "%{$winnerSearch}%")
                        ->orWhereHas('user', function ($userQuery) use ($winnerSearch) {
                            $userQuery->where('name', 'like', "%{$winnerSearch}%")
                                ->orWhere('email', 'like', "%{$winnerSearch}%")
                                ->orWhere('mobile', 'like', "%{$winnerSearch}%");
                        });
                });
            })
            ->when(!empty($winnerStatus), function ($query) use ($winnerStatus) {
                $query->where('status', $winnerStatus);
            })
            ->orderBy('rank', 'asc')
            ->paginate($perPage, ['*'], 'winners_page')
            ->withQueryString();

        // 🏆 TOP 3 WINNERS
        $topWinners = QuizWinner::with(['user', 'rankReward'])
            ->where('quiz_id', $quiz->id)
            ->whereIn('rank', [1, 2, 3])
            ->get()
            ->keyBy('rank');

        $this->attachPointHistoryToWinners($winners, $quiz);    
        $this->attachPointHistoryToWinners($topWinners, $quiz);

        // ✅ Check if "Update Quiz Play Data" button should be visible
        // Condition: ALWAYS show when quiz.complete_status is processing or completed
        $isAnswerTimeFinished = $quiz->isAnswerTimeFinished();
        $answerEndTime = $quiz->getAnswerEndTime();
        $canShowUpdatePlayDataBtn = false;
        $showUpdatePlayDataBtn = false;

        if (in_array($quiz->complete_status, ['processing', 'completed'])) {
            $canShowUpdatePlayDataBtn = true;
            $showUpdatePlayDataBtn = true;
        } elseif ($quiz->visibility === 'fastest_finger_first') {
            $canShowUpdatePlayDataBtn = true;
            $showUpdatePlayDataBtn = $isAnswerTimeFinished;
        } else {
            $tenantId = $quiz->tenant_id ?: getSuperAdminTenantId();
            
            $nextUpcomingQuiz = null;
            if (!empty($quiz->scheduled_at)) {
                $nextUpcomingQuiz = Quiz::where('id', '!=', $quiz->id)
                    ->where('visibility', '!=', 'fastest_finger_first')
                    ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                    ->where('scheduled_at', '>', $quiz->scheduled_at)
                    ->orderBy('scheduled_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->first();
            }
            
            if (!$nextUpcomingQuiz || ($nextUpcomingQuiz && $nextUpcomingQuiz->complete_status === 'pending')) {
                $canShowUpdatePlayDataBtn = true;               
            }
            $showUpdatePlayDataBtn = $canShowUpdatePlayDataBtn && $isAnswerTimeFinished;
        }

        return view('superadmin.quizzes.joined-users-quiz', compact(
            'quiz', 
            'joinedUsers',
            'totalJoinedCount',
            'totalPlayedCount',
            'totalLeftCount',
            'totalWaitingCount',
            'showUpdatePlayDataBtn',
            'canShowUpdatePlayDataBtn',
            'isAnswerTimeFinished',
            'answerEndTime',
            'winners',
            'topWinners',
            'activeTab'
        ));
    }
    // *************joined user quiz end**********


    public function resetQuizStatus($quiz_id)
    {
        try {
            // 1. Quiz details fetch karein
            $quiz = DB::table('quizzes')->where('id', $quiz_id)->first();

            if (!$quiz) {
                return redirect()->back()->with('error', '❌ Quiz not found!');
            }

            // 2. Time Validation: Check karein ki scheduled time future mein hai ya nahi
            $scheduledTime = Carbon::parse($quiz->scheduled_at); 
            $now = Carbon::now();

            // Agar current time, scheduled time ke barabar ya uske baad hai, toh BLOCK kar do
            if ($now->greaterThanOrEqualTo($scheduledTime)) {
                return redirect()->back()->with('error', '❌ Update Failed Quiz ID ' . $quiz_id . ': Is quiz ka play time already start ho chuka hai ya nikal gaya hai. Aap ise ab pending nahi kar sakte.');
            }

            // 3. Agar time future mein hai, toh dono tables update karein
            DB::table('quizzes')
                ->where('id', $quiz_id)
                ->update(['complete_status' => 'pending']);

            DB::table('quiz_status')
                ->where('quiz_id', $quiz_id)
                ->update(['status' => 'pending']);

            // 4. Success message ke saath wapis redirect karein
            return redirect()->back()->with('success', '✅ Quiz ID ' . $quiz_id . ' ka status successfully Pending kar diya gaya hai!');
            
        } catch (\Exception $e) {
            // Agar koi error aaye toh
            return redirect()->back()->with('error', '❌ Status update failed: ' . $e->getMessage());
        }
    }

    // **************reset status end**********
}