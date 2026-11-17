<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\UserSubscription;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->from ? \Carbon\Carbon::parse($request->from) : now()->subWeeks(8);
        $to = $request->to ? \Carbon\Carbon::parse($request->to) : now();

        $revenueByWeek = Transaction::where('type', 'subscription')
            ->where('status', 'success')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('YEARWEEK(created_at) as yw, SUM(amount) as total')
            ->groupBy('yw')
            ->orderBy('yw')
            ->get();

        $topCreators = collect(); // placeholder for a future creator-earnings query

        return view('superadmin.reports.index', compact('revenueByWeek', 'topCreators', 'from', 'to'));
    }

    public function userQuizReport(Request $request)
    {
        $search = $request->get('search');
        $sortColumn = $request->get('sort', 'correct_answers');
        $sortDirection = $request->get('dir', 'desc');

        $allowedSorts = [
            'name' => 'name',
            'quizzes_played' => 'quizzes_played',
            'total_questions' => 'total_questions',
            'unanswered' => 'unanswered',
            'correct_answers' => 'correct_answers',
            'wrong_answers' => 'wrong_answers',
            'wallet_balance' => 'wallet_balance',
            'leader_board_point' => 'leader_board_point',
        ];

        if (!array_key_exists($sortColumn, $allowedSorts)) {
            $sortColumn = 'correct_answers';
        }
        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }
        
        $query = \App\Models\User::withCount([
            'quizUsers as quizzes_played',
            'quizAnswers as attempted_questions',
            'quizAnswers as correct_answers' => function($q) {
                $q->where('status', 1);
            },
            'quizAnswers as wrong_answers' => function($q) {
                $q->where('status', 0);
            }
        ])
        ->withSum('quizzesPlayed as total_questions', 'question_limit');

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
        }

        $perPage = $request->get('per_page', 25);
        $dbSortColumn = $sortColumn === 'unanswered' ? 'total_questions' : $allowedSorts[$sortColumn];
        $users = $query->orderBy($dbSortColumn, $sortDirection)->paginate($perPage);
        
        $users->getCollection()->transform(function ($player) {
            $total = (int) ($player->total_questions ?? 0);
            $attempted = (int) ($player->attempted_questions ?? 0);
            $player->unanswered = max(0, $total - $attempted);
            return $player;
        });

        if ($sortColumn === 'unanswered') {
            $users->setCollection(
                $sortDirection === 'desc' 
                    ? $users->getCollection()->sortByDesc('unanswered')->values() 
                    : $users->getCollection()->sortBy('unanswered')->values()
            );
        }

        return view('superadmin.reports.user_quiz_report', compact('users', 'search', 'sortColumn', 'sortDirection'));
    }
}
