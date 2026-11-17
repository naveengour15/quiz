<?php

namespace App\Http\Controllers\fsrapp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\QuizUser;
use App\Models\QuizWinner;
use App\Models\Banner;
use Illuminate\Support\Facades\Auth;

class HistoryController extends Controller
{
   
    public function index()
    {
        $user = Auth::user() ?? \App\Models\User::first();
        $userId = $user ? $user->id : 0;

        $banners = Banner::where('status', 'active')->latest()->get();

        $limit = (int) request()->query('limit', 10);

        // All completed quizzes count
        $totalCount = Quiz::where('complete_status', 'completed')->count();

        // All completed quizzes list
        $histories = Quiz::select(['id', 'tenant_id', 'category_id', 'title', 'complete_status', 'updated_at'])
            ->where('complete_status', 'completed')
            ->with(['category:id,name,icon'])
            ->latest('updated_at')
            ->take($limit)
            ->get();

        $quizIds = $histories->pluck('id')->toArray();

        // Fast single query for played counts instead of correlated subqueries
        $playedCounts = !empty($quizIds)
            ? QuizUser::whereIn('quiz_id', $quizIds)
                ->selectRaw('quiz_id, count(*) as count')
                ->groupBy('quiz_id')
                ->pluck('count', 'quiz_id')
            : collect();

        $histories->transform(function ($quiz) use ($playedCounts) {
            $quiz->quiz_users_count = $playedCounts[$quiz->id] ?? 0;
            return $quiz;
        });

        return view('fsrapp.history.index', compact('histories', 'user', 'limit', 'totalCount', 'banners'));
    }

    public function leaderboard($id)
    {
        // ✅ 1. Smart Back URL Logic
        $previousUrl = url()->previous();
        $currentBaseUrl = url()->current();

        if ($previousUrl && !str_contains($previousUrl, $currentBaseUrl)) {
            session(['fsr_leaderboard_back_url' => $previousUrl]);
        }

      
        $backUrl = session('fsr_leaderboard_back_url', route('fsrapp.home'));

        $quiz = Quiz::select(['id', 'title', 'category_id', 'complete_status', 'switch_option','visibility', 'scheduled_at'])
            ->with(['category:id,name,icon'])
            ->withCount('questions')
            ->findOrFail($id);

        if ($quiz->complete_status !== 'completed' && $quiz->scheduled_at) {
            $secondsUntilStart = \Carbon\Carbon::now()->diffInSeconds(\Carbon\Carbon::parse($quiz->scheduled_at), false);
            if ($secondsUntilStart > 2400) {
                return redirect()->route('fsrapp.home')->with('warning', __('app.quiz_join_40min_warning'));
            }
        }

        $user = Auth::user() ?? \App\Models\User::first();
        // $banners = Banner::where('status', 'active')->latest()->get();

             // Quiz table se latest quiz ID
            $latestQuizId = Quiz::latest('id')->value('id');

            // Current quiz latest hai ya history
            $pageType = ((int) $id === (int) $latestQuizId)
                ? 'quiz_leaderboard'
                : 'quiz_history_leaderboard';

            // ALL + current page type banners
            $banners = Banner::where('status', 'active')
                ->where(function ($query) use ($pageType) {
                    $query->whereJsonContains('type', 'all')
                        ->orWhereJsonContains('type', $pageType);
                })
                ->latest()
                ->get();

        $isFff = ($quiz->visibility === 'fastest_finger_first');
        if (!$isFff) {
            // Only recalculate if unranked entries exist to prevent massive loops/CPU spikes on every load-more
            if (\App\Models\QuizWinner::where('quiz_id', $id)->whereNull('rank')->exists()) {
                \App\Models\QuizWinner::recalculateRanks((int) $id);
            }
        }

        $limit = (int) request()->query('limit', 10);
        $totalCount = \App\Models\QuizWinner::where('quiz_id', $id)->count();

        $leaderboard = \App\Models\QuizWinner::select(['id', 'quiz_id', 'user_id', 'rank', 'total_correct_answer', 'total_time', 'reward'])
            ->where('quiz_id', $id)
            ->with('user:id,name,profile_photo')
            ->orderBy('rank', 'asc')
            ->take($limit)
            ->get()
            ->map(function ($w, $idx) {
                return (object) [
                    'rank' => $w->rank ?: ($idx + 1),
                    'user_id' => $w->user_id,
                    'name' => $w->user?->name ?? 'Player #' . $w->user_id,
                    'profile_photo' => $w->user?->profile_photo ?? null,
                    'correct' => $w->total_correct_answer ?? 0,
                    'reward' => (float) ($w->reward ?? (0)),
                    'total_time' => $w->total_time ?? 0,
                ];
            });

        if ($leaderboard->isEmpty()) {
            $totalCount = \App\Models\QuizQuestionUserAnswer::where('quiz_id', $id)
                ->selectRaw('user_id')
                ->groupBy('user_id')
                ->havingRaw('count(id) >= 1')
                ->get()
                ->count();

            $leaderboard = \App\Models\QuizQuestionUserAnswer::selectRaw('user_id, sum(status) as score, sum(total_time) as total_time')
                ->where('quiz_id', $id)
                ->groupBy('user_id')
                ->havingRaw('count(id) >= 1')
                ->orderByDesc(\DB::raw('sum(status)'))
                ->orderBy(\DB::raw('sum(total_time)'))
                ->with('user:id,name,profile_photo')
                ->take($limit)
                ->get()
                ->map(function ($u, $idx) use ($isFff) {
                    return (object) [
                        'rank' => $idx + 1,
                        'user_id' => $u->user_id,
                        'name' => $u->user?->name ?? 'Player #' . $u->user_id,
                        'profile_photo' => $u->user?->profile_photo ?? null,
                        'correct' => (int) ($u->score ?? 0),
                        'reward' => $isFff ? (float) ($u->score ?? 0) : (float) (($u->score ?? 0) * 10),
                        'total_time' => (float) ($u->total_time ?? 0),
                    ];
                });
        }

        $myEntryData = null;
        if ($user && $user->id) {
            $myWin = \App\Models\QuizWinner::where('quiz_id', $id)
                ->where('user_id', $user->id)
                ->with('user:id,name,profile_photo')
                ->first();

            if ($myWin) {
                $myEntryData = (object) [
                    'rank' => $myWin->rank,
                    'user_id' => $myWin->user_id,
                    'name' => $myWin->user?->name ?? 'You',
                    'profile_photo' => $myWin->user?->profile_photo ?? null,
                    'correct' => $myWin->total_correct_answer ?? 0,
                    'reward' => (float) ($myWin->reward ?? 0),
                    'total_time' => $myWin->total_time ?? 0,
                ];
            } else {
                $queryAns = \App\Models\QuizQuestionUserAnswer::selectRaw('user_id, sum(status) as score, sum(total_time) as total_time');

                $myAns = $queryAns->where('quiz_id', $id)
                    ->where('user_id', $user->id)
                    ->groupBy('user_id')
                    ->with('user:id,name,profile_photo')
                    ->first();
                if ($myAns) {
                    $score = (int) ($myAns->score ?? 0);
                    $myEntryData = (object) [
                        'rank' => '-',
                        'user_id' => $myAns->user_id,
                        'name' => $myAns->user?->name ?? 'You',
                        'profile_photo' => $myAns->user?->profile_photo ?? null,
                        'correct' => $score,
                        'reward' => $isFff ? (float) $score : ($score >= 1 ? (float) ($score * 10) : 0),
                        'total_time' => (float) ($myAns->total_time ?? 0),
                    ];
                }
            }
        }
        
         // ✅ YAHAN HELPER FUNCTION CALL KAREIN
        $this->attachPointHistoryToLeaderboard($leaderboard, $quiz);
        
        if ($myEntryData) {
            // myEntryData single object hai, isliye collection me wrap karke bhej rahe hain
            $this->attachPointHistoryToLeaderboard(collect([$myEntryData]), $quiz);
            $myEntryData = collect([$myEntryData])->first(); // Wapas object me convert
        }

        return view('fsrapp.history.leaderboard', compact('quiz', 'id', 'user', 'leaderboard', 'limit', 'totalCount','banners', 'myEntryData','backUrl','pageType'));
    }

    public function answerSheet($id)
    {
        $quiz = Quiz::with('category')->findOrFail($id);

        // Jab tak quiz answer submit time complete nahi hua ho, answer sheet page direct hit hone par home page redirect karein
        if (!$quiz->isAnswerTimeFinished()) {
            return redirect()->route('fsrapp.home');
        }

        $user = Auth::user() ?? \App\Models\User::first();
        $userId = $user ? $user->id : 0;
        $banners = Banner::where('status', 'active')->latest()->get();

        // Fetch questions for the quiz
        $questions = \App\Models\QuizQuestion::with('question')
            ->where('quiz_id', $id)
            ->where(function($q) {
                $q->whereNull('is_switch_question')->orWhere('is_switch_question', 0);
            })
            ->orderBy('order', 'asc')
            ->get();

        // Fetch user's answers from DB
        $dbAnswers = \App\Models\QuizQuestionUserAnswer::where('quiz_id', $id)
            ->where('user_id', $userId)
            ->get()
            ->keyBy('question_id');

        $enrollment = \App\Models\QuizUser::where('quiz_id', $id)
            ->where('user_id', $userId)
            ->first();
        $hasSwitched = (bool) ($enrollment && $enrollment->switch_option);
        $switchedReplacedQId = $hasSwitched ? ($enrollment->question_id ?? null) : null;

        $questionLimit = ($quiz && !empty($quiz->question_limit) && (int)$quiz->question_limit > 0)
            ? (int)$quiz->question_limit
            : 10;

        // Check if user answered any switch questions
        $switchQuestions = \App\Models\QuizQuestion::with('question')
            ->where('quiz_id', $id)
            ->where('is_switch_question', 1)
            ->orderBy('order', 'asc')
            ->get();

        $questionsMap = [];
        foreach ($questions as $q) {
            if ($hasSwitched && $switchedReplacedQId && $q->id == $switchedReplacedQId) {
                continue;
            }
            $questionsMap[$q->id] = $q;
        }
        foreach ($switchQuestions as $sq) {
            if ($hasSwitched || isset($dbAnswers[$sq->id])) {
                $questionsMap[$sq->id] = $sq;
            }
        }

        $sheet = [];
        $correctCount = 0;
        $totalTimeSum = 0;

        foreach ($questionsMap as $q) {
            $qId = $q->id;
            $submitted = null;
            $isCorrect = false;
            $timeTaken = 0;

            if (isset($dbAnswers[$qId])) {
                $submitted = $dbAnswers[$qId]->answer;
                $isCorrect = (bool) $dbAnswers[$qId]->status;
                $timeTaken = (float) $dbAnswers[$qId]->total_time;
            }

            // Options logic: combine Hindi and English options
            $opts = $q->options;
            if (is_string($opts)) {
                $opts = json_decode($opts, true) ?: [];
            }
            if (!is_array($opts)) {
                $opts = [];
            }

            $hindiOpts = $q->hindi_options;
            if (is_string($hindiOpts)) {
                $hindiOpts = json_decode($hindiOpts, true) ?: [];
            }
            if (empty($hindiOpts) && is_object($q->question) && !empty($q->question->hindi_options)) {
                $hRel = $q->question->hindi_options;
                $hindiOpts = is_string($hRel) ? (json_decode($hRel, true) ?: []) : (is_array($hRel) ? $hRel : []);
            }
            if (!is_array($hindiOpts)) {
                $hindiOpts = [];
            }

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

            // Raw correct answer candidate
            $rawCorrect = $q->correct_answer;
            if ($rawCorrect === null || trim((string)$rawCorrect) === '') {
                $rawCorrect = $q->hindi_correct_answer;
            }
            if (($rawCorrect === null || trim((string)$rawCorrect) === '') && is_object($q->question)) {
                $rawCorrect = $q->question->correct_answer ?: $q->question->hindi_correct_answer;
            }
            if (($rawCorrect === null || trim((string)$rawCorrect) === '') && !empty($q->bot_correct_answer) && strtolower((string)$q->bot_correct_answer) !== 'no') {
                $rawCorrect = $q->bot_correct_answer;
            }

            $correctAnsStr = $this->resolveMatchingOption($rawCorrect, $cleanOptions);
            if ($correctAnsStr === '' && !empty($opts)) {
                $correctAnsStr = $this->resolveMatchingOption($rawCorrect, $opts);
            }
            if ($correctAnsStr === '') {
                $correctAnsStr = (string)($rawCorrect ?? 'N/A');
            }

            $userAnsStr = $this->resolveMatchingOption($submitted ?? '', $cleanOptions);
            if ($userAnsStr === '' && $submitted !== null && $submitted !== '') {
                $userAnsStr = (string)$submitted;
            }

            if ($correctAnsStr !== '' && $userAnsStr !== '') {
                $normC = preg_replace('/\s+/u', ' ', mb_strtolower(trim($correctAnsStr), 'UTF-8'));
                $normU = preg_replace('/\s+/u', ' ', mb_strtolower(trim($userAnsStr), 'UTF-8'));
                if ($normC === $normU) {
                    $isCorrect = true;
                } else {
                    $cBase = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $normC));
                    $uBase = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $normU));
                    if ($cBase !== '' && $uBase !== '' && $cBase === $uBase) {
                        $isCorrect = true;
                    }
                }
            }

            if ($isCorrect) {
                $correctCount++;
            }
            $totalTimeSum += $timeTaken;

            $mediaUrl = $q->media_url ?: ($q->question?->media_url ?? null);
            if (!empty($mediaUrl)) {
                $mediaUrl = trim((string) $mediaUrl);
                $cleanPath = preg_replace('/^https?:\/\/[^\/]+\//i', '', $mediaUrl);
                $cleanPath = ltrim($cleanPath, '/');
                $awsUrl = env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com');
                if (!empty($awsUrl)) {
                    $mediaUrl = rtrim($awsUrl, '/') . '/' . $cleanPath;
                } else {
                    $mediaUrl = asset($mediaUrl);
                }
            } else {
                $mediaUrl = null;
            }

            $questionAudio = $q->question_audio ?: ($q->audio_file ?: ($q->question?->question_audio ?: ($q->question?->audio_file ?? null)));
            if (!empty($questionAudio)) {
                $questionAudio = trim((string) $questionAudio);
                $cleanPath = preg_replace('/^https?:\/\/[^\/]+\//i', '', $questionAudio);
                $cleanPath = ltrim($cleanPath, '/');
                $awsUrl = env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com');
                if (!empty($awsUrl)) {
                    $questionAudio = rtrim($awsUrl, '/') . '/' . $cleanPath;
                } else {
                    $questionAudio = asset($questionAudio);
                }
            } else {
                $questionAudio = null;
            }

            $sheet[] = (object) [
                'question_id' => $qId,
                'question' => $q->question_text ?: ($q->question?->question_text ?? 'Question'),
                'hindi_question' => $q->hindi_question ?: ($q->question?->hindi_question ?? null),
                'question_audio' => $questionAudio,
                'media_url' => $mediaUrl,
                'options' => $cleanOptions ?: $opts,
                'correct_answer' => $correctAnsStr ?: ($q->correct_answer ?? 'N/A'),
                'user_answer' => $userAnsStr ?: ($submitted ?? 'No Answer (Auto Selected)'),
                'is_correct' => $isCorrect,
                'total_time' => $timeTaken
            ];
        }

        if ($hasSwitched && $switchedReplacedQId) {
            $sheet = array_values(array_filter($sheet, function ($item) use ($switchedReplacedQId) {
                return ($item->question_id ?? null) != $switchedReplacedQId;
            }));
        }

        if ($questionLimit > 0 && count($sheet) > $questionLimit) {
            $sheet = array_slice($sheet, 0, $questionLimit);
        }

        $correctCount = 0;
        $totalTimeSum = 0;
        foreach ($sheet as $item) {
            if (!empty($item->is_correct)) $correctCount++;
            $totalTimeSum += (float) ($item->total_time ?? 0);
        }

        $totalQuestionsCount = count($sheet);

        return view('fsrapp.history.historyanssheet', compact(
            'quiz',
            'id',
            'sheet',
            'correctCount',
            'totalTimeSum',
            'totalQuestionsCount',
            'banners'
        ));
    }

    private function resolveMatchingOption($targetStr, array $options): string
    {
        if ($targetStr === null || trim((string)$targetStr) === '') {
            return '';
        }

        $targetStr = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', (string)$targetStr));
        if (empty($options)) {
            return $targetStr;
        }

        $stringOptions = [];
        foreach ($options as $k => $v) {
            if (is_array($v)) {
                $stringOptions[$k] = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', (string)($v['option_text'] ?? $v['text'] ?? $v['label'] ?? reset($v) ?? '')));
            } else {
                $stringOptions[$k] = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', (string)$v));
            }
        }

        $normTarget = preg_replace('/\s+/u', ' ', mb_strtolower($targetStr, 'UTF-8'));

        // 1. Direct match
        foreach ($stringOptions as $optStr) {
            $normOpt = preg_replace('/\s+/u', ' ', mb_strtolower($optStr, 'UTF-8'));
            if ($normTarget === $normOpt) {
                return $optStr;
            }
        }

        // 2. Option prefix stripped match (e.g. 'A. Option', '1. Option', '(A) Option', 'Option A: Text')
        $cleanedPrefix = preg_replace('/^(?:(?:option|opt)[\s_]*[a-f\d]+[\s:\-\.]*|[a-f\d]+[\.\)\:\-]\s*|\([a-f\d]+\)\s*)/iu', '', $normTarget);
        $cleanedPrefix = trim($cleanedPrefix);
        if ($cleanedPrefix !== '' && $cleanedPrefix !== $normTarget) {
            foreach ($stringOptions as $optStr) {
                $normOpt = preg_replace('/\s+/u', ' ', mb_strtolower($optStr, 'UTF-8'));
                if ($cleanedPrefix === $normOpt) {
                    return $optStr;
                }
            }
        }

        // 3. Match option letters/keys
        $cleanedLetter = preg_replace('/^(?:option|opt)[\s_]*/u', '', $normTarget);
        $letters = ['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3, 'e' => 4, 'f' => 5];
        if (isset($letters[$cleanedLetter]) && isset($stringOptions[$letters[$cleanedLetter]])) {
            return $stringOptions[$letters[$cleanedLetter]];
        }

        // 4. Numeric index (0, 1, 2, 3...)
        if (is_numeric($cleanedLetter)) {
            $idx = (int)$cleanedLetter;
            if (isset($stringOptions[$idx])) {
                return $stringOptions[$idx];
            }
            if ($idx > 0 && isset($stringOptions[$idx - 1])) {
                return $stringOptions[$idx - 1];
            }
        }

        // 5. Bilingual base (Hindi/English) and inner parenthetical matches
        $targetBase = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $targetStr));
        $normTargetBase = preg_replace('/\s+/u', ' ', mb_strtolower($targetBase, 'UTF-8'));

        preg_match('/\((.*?)\)/u', $targetStr, $tMatch);
        $targetInner = isset($tMatch[1]) ? trim($tMatch[1]) : '';
        $normTargetInner = preg_replace('/\s+/u', ' ', mb_strtolower($targetInner, 'UTF-8'));

        foreach ($stringOptions as $optStr) {
            $optBase = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $optStr));
            $normOptBase = preg_replace('/\s+/u', ' ', mb_strtolower($optBase, 'UTF-8'));

            preg_match('/\((.*?)\)/u', $optStr, $oMatch);
            $optInner = isset($oMatch[1]) ? trim($oMatch[1]) : '';
            $normOptInner = preg_replace('/\s+/u', ' ', mb_strtolower($optInner, 'UTF-8'));

            if (($normTarget !== '' && $normTarget === $normOptBase) ||
                ($normOptInner !== '' && $normTarget === $normOptInner) ||
                ($normTargetBase !== '' && $normTargetBase === $normOptBase) ||
                ($normTargetBase !== '' && $normOptInner !== '' && $normTargetBase === $normOptInner) ||
                ($normTargetInner !== '' && $normTargetInner === $normOptBase) ||
                ($normTargetInner !== '' && $normOptInner !== '' && $normTargetInner === $normOptInner) ||
                ($normTargetBase !== '' && $normOpt !== '' && $normTargetBase === $normOpt) ||
                ($normTargetInner !== '' && $normOpt !== '' && $normTargetInner === $normOpt)
            ) {
                return $optStr;
            }
        }

        return $targetStr;
    }

    // *********leaderboard point only show***************
   
    private function attachPointHistoryToLeaderboard($items, $quiz)
    {
        // Collection me convert karein taaki uniform processing ho
        $collection = collect($items);

        // 1. Agar visibility 'leaderboard' NAHI hai, toh sabke liye 0 set kar do
        if ($quiz->visibility !== 'leaderboard') {
            foreach ($collection as $item) {
                $item->point_history = (object) [
                    'old_point' => 0,
                    'point' => 0,
                    'new_point' => 0,
                    'type' => 'none',
                    'created_at' => null,
                ];
            }
            return $items instanceof \Illuminate\Support\Collection ? $collection : $collection->first();
        }

        // 2. Agar 'leaderboard' hai, toh user_ids nikalo
        $userIds = $collection->pluck('user_id')->filter()->toArray();

        if (empty($userIds)) {
            return $items instanceof \Illuminate\Support\Collection ? $collection : $collection->first();
        }

        // Database se pure row fetch karo aur user_id ke hisaab se map kar lo (No N+1)
        $histories = \App\Models\LeaderPointHistory::whereIn('user_id', $userIds)
            ->where('quiz_id', $quiz->id)
            ->get()
            ->keyBy('user_id'); 

        // 3. Har item ke sath uska history attach kar do
        foreach ($collection as $item) {
            $item->point_history = $histories->get($item->user_id) ?? (object) [
                'old_point' => 0,
                'point' => 0,
                'new_point' => 0,
                'type' => 'none',
                'created_at' => null,
            ];
        }

        // Wapas same format me return karo (Collection ya single object)
        return $items instanceof \Illuminate\Support\Collection ? $collection : $collection->first();
    }
    // *********leaderboard point only show***************
}
