<?php

namespace App\Http\Controllers\fsrapp;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Quiz;
use App\Models\QuizNotification;
use App\Models\QuizUser;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AllQuizListController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = session('tenant_id') ?? 1;
        $userId = Auth::id();

        $categories = Category::all();
        $banners = Banner::where('status', 'active')->latest()->get();
        $query = Quiz::with(['rankRewards', 'quizType', 'category'])
            ->whereIn('tenant_id', [$tenantId, 1])
            ->where('approve_status', 'approved')
            ->where('complete_status', 'pending');

        if ($request->has('category_id') && $request->category_id != '') {
            $query->where('category_id', $request->category_id);
        }

        $quizzes = $query->orderBy('scheduled_at', 'desc')->paginate(10)->withQueryString();

        $joinedArray = [];
        $inactiveArray = [];
        $notifiedArray = [];

        if ($userId) {
            $joinedArray = QuizUser::where('user_id', $userId)
                ->where('status', 'active')
                ->pluck('quiz_id')
                ->unique()
                ->values()
                ->toArray();

            $inactiveArray = QuizUser::where('user_id', $userId)
                ->where('status', 'inactive')
                ->pluck('quiz_id')
                ->unique()
                ->values()
                ->toArray();

            $notifiedArray = QuizNotification::where('user_id', $userId)
                ->pluck('quiz_id')
                ->toArray();
        }

        return view('fsrapp.allquizlist.index', compact(
            'categories',
            'quizzes',
            'joinedArray',
            'inactiveArray',
            'notifiedArray',
            'banners'
        ));
    }
}
