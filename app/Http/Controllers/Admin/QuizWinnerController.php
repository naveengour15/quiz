<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizWinner;
use App\Models\QuizUser;
use App\Models\QuizQuestionUserAnswer;
use App\Models\LeaderPointHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuizWinnerController extends Controller
{
    public function winnerList(Request $request, $quiz)
    {
        if (!$quiz instanceof Quiz) {
            $quiz = Quiz::resolveQuiz($quiz);
        }

        if ($quiz->tenant_id != getTenantId()) {
            abort(403, 'Unauthorized access.');
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

        return view('admin.quizzes.winners', compact('quiz', 'winners','topWinners'));
    }

    // NEW: AJAX method to fetch user's specific answers for a quiz
    public function getUserAnswers($quizId, $userId)
    {
        if (!is_numeric($quizId)) {
            $decrypted = Quiz::decryptId($quizId);
            if (!empty($decrypted) && is_numeric($decrypted)) {
                $quizId = (int) $decrypted;
            }
        }

        $quiz = Quiz::where('tenant_id', getTenantId())
            ->findOrFail($quizId);
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

                    if ($hOptText !== '' && $optText !== '' && strcasecmp($optText, $hOptText) !== 0) {
                        $cleanOptions[] = $hOptText . ' (' . $optText . ')';
                    } elseif ($hOptText !== '') {
                        $cleanOptions[] = $hOptText;
                    } elseif ($optText !== '') {
                        $cleanOptions[] = $optText;
                    }
                }

                $rawCorrect = $question?->correct_answer ?? 
                            $question?->hindi_correct_answer ?? 
                            $question?->question?->correct_answer ?? 
                            $question?->question?->hindi_correct_answer ?? 
                            $question?->bot_correct_answer ?? '';

                $correctAns = '';
                if ($rawCorrect !== null && trim((string)$rawCorrect) !== '') {
                    $correctAns = $quizRepo->resolveMatchingOptionStr((string)$rawCorrect, $cleanOptions ?: $opts);
                    if ($correctAns === '') {
                        $correctAns = (string)$rawCorrect;
                    }
                }

                $userAnsRaw = $item->answer ?? '';
                $userAns = (string) $userAnsRaw;
                if ($userAns !== '' && !empty($cleanOptions)) {
                    $resolvedUser = $quizRepo->resolveMatchingOptionStr($userAns, $cleanOptions);
                    if ($resolvedUser !== '') $userAns = $resolvedUser;
                }

                $finalUserAns = (trim($userAns) !== '') ? $userAns : 'Skipped';
                $finalCorrectAns = (trim($correctAns) !== '') ? $correctAns : 'N/A';

                $qText = $question ? ($question->question_text ?? 'Question text missing') : 'Question deleted';
                if ($question && !empty($question->hindi_question)) {
                    $qText = $question->hindi_question . (!empty($question->question_text) ? ' (' . $question->question_text . ')' : '');
                }

                return [
                    'question_text'  => $qText,
                    'user_answer'    => $finalUserAns,
                    'correct_answer' => $finalCorrectAns,
                    'is_correct'     => (bool) $item->status,
                    'ans_time'       => $item->ans_time ?? 0,
                    'total_time'     => $item->total_time ?? 10,
                ];
            });

            return response()->json([
                'success' => true,
                'data'    => $formattedData
            ]);

        } catch (\Exception $e) {
            // 3. Agar koi error aaye, toh poora detail ke sath error log karein
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

        if ($quiz->tenant_id != getTenantId()) {
            abort(403, 'Unauthorized access.');
        }
        $perPage = $request->get('per_page', 10);

        if (\App\Services\RedisQuizService::isAvailable()) {
            try {
                do {
                    $drained = \App\Services\RedisQuizService::collectAndBulkUpsertPendingEnrollments(5000);
                } while ($drained >= 5000);
            } catch (\Throwable $e) {
                \Log::warning("Admin QuizWinnerController@joinedUsers Redis drain error: " . $e->getMessage());
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
            \Log::warning("Admin QuizWinnerController auto-sync out_status error: " . $e->getMessage());
        }

        //  1. Total Joined Users Count (Sabhi users)
        $totalJoinedCount = QuizUser::where('quiz_id', $quiz->id)->count();

        //  2. Total Played Users Count (out_status 'in')
        $totalPlayedCount = QuizUser::where('quiz_id', $quiz->id)
            ->where('out_status', 'in')
            ->count();

        //  3. Total Left Users Count (out_status 'out')
        $totalLeftCount = QuizUser::where('quiz_id', $quiz->id)
            ->where('out_status', 'out')
            ->count();

        //  4. Total Waiting Users Count (Jinhone 0 answer submit kiya hai aur out nahi hue)
        $totalWaitingCount = QuizUser::where('quiz_id', $quiz->id)
            ->where(function ($query) {
                $query->whereNull('out_status')->orWhere('out_status', 'waiting');
            })
            ->count();

        //  Main Table Data
        $joinedUsers = QuizUser::with([
                'user' => function ($query) {
                    $query->withCount('quizUsers');
                }
            ])
            ->withCount(['quizQuestionUserAnswers as answer_count' => function ($query) use ($quiz) {
                $query->where('quiz_id', $quiz->id);
            }])
            ->where('quiz_id', $quiz->id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('fifty_fifty'), function ($query) use ($request) {
                $query->where('fifty_fifty', $request->fifty_fifty);
            })
            ->when($request->filled('switch_option'), function ($query) use ($request) {
                $query->where('switch_option', $request->switch_option);
            })
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

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
            $tenantId = $quiz->tenant_id ?: getTenantId();
            
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

        return view('admin.quizzes.joined-users-quiz', compact(
            'quiz', 
            'joinedUsers',
            'totalJoinedCount',
            'totalPlayedCount',
            'totalLeftCount',
            'totalWaitingCount',
            'showUpdatePlayDataBtn',
            'canShowUpdatePlayDataBtn',
            'isAnswerTimeFinished',
            'answerEndTime'
        ));
    }
    // *************joined user quiz end**********
}