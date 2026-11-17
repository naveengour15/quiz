<?php

namespace App\Http\Controllers\androidapi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\androidapi\Traits\ResolvesAndroidUser;
use App\Models\Category;
use App\Models\Quiz;
use App\Models\QuizNotification;
use App\Models\QuizUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AllQuizListController extends Controller
{
    use ResolvesAndroidUser;

    public function index(Request $request)
    {
        $tenantId = $request->header('X-Tenant-Id') ?? 1;
        $user = $this->getAuthUser($request);
        $userId = $user?->id;

        $categories = Category::all();

        $query = Quiz::with(['rankRewards', 'quizType', 'category'])
            ->whereIn('tenant_id', [$tenantId, 1])
            ->where('approve_status', 'approved')
            ->where('complete_status', 'pending');

        if ($request->has('category_id') && $request->category_id != '') {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search') && $request->search != '') {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $quizzes = $query->orderBy('scheduled_at', 'desc')->paginate(15);

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

        // Attach joined and notified flags to quiz items
        $quizzes->getCollection()->transform(function ($quiz) use ($joinedArray, $inactiveArray, $notifiedArray) {
            $quiz->is_joined = in_array($quiz->id, $joinedArray);
            $quiz->is_inactive = in_array($quiz->id, $inactiveArray);
            $quiz->is_notified = in_array($quiz->id, $notifiedArray);
            return $quiz;
        });

        return response()->json([
            'success' => true,
            'message' => 'Quizzes list fetched successfully.',
            'categories' => $categories,
            'quizzes' => $quizzes,
            'joined_ids' => $joinedArray,
            'inactive_ids' => $inactiveArray,
            'notified_ids' => $notifiedArray
        ]);
    }

    public function list(Request $request)
    {
        $response = $this->index($request)->getData(true);
        return response()->json(['success' => true, 'data' => $response['quizzes']['data'] ?? []]);
    }

    public function upcoming(Request $request)
    {
        $tenantId = $request->header('X-Tenant-Id') ?? 1;
        $quizzes = Quiz::query()
            ->whereIn('tenant_id', [$tenantId, 1])
            ->where('approve_status', 'approved')
            ->where('complete_status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get(['id', 'title', 'scheduled_at', 'total_reward_amount', 'entry_fee']);
        return response()->json(['success' => true, 'data' => $quizzes]);
    }

    public function practice(Request $request)
    {
        $tenantId = $request->header('X-Tenant-Id') ?? 1;
        $quizzes = Quiz::query()
            ->whereIn('tenant_id', [$tenantId, 1])
            ->where('approve_status', 'approved')
            ->where(function ($query) {
                $query->where('type', 'Practice')
                    ->orWhere('type', 1)
                    ->orWhere('total_reward_amount', 0)
                    ->orWhere('no_of_points', 0);
            })
            ->orderBy('id')
            ->limit(20)
            ->get(['id', 'title', 'total_questions', 'category_id']);
        return response()->json(['success' => true, 'data' => $quizzes]);
    }
}
