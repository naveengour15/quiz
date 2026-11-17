<?php

namespace App\Http\Controllers\androidapi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\androidapi\Traits\ResolvesAndroidUser;
use App\Models\Quiz;
use App\Models\QuizUser;
use App\Models\QuizWinner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HistoryController extends Controller
{
    use ResolvesAndroidUser;

    public function getHistory(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $userId = $user->id;

        // Support pagination: page & per_page (or limit)
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(50, (int) $request->input('per_page', $request->input('limit', 20))));
        $offset = ($page - 1) * $perPage;

        // Fetch all completed quiz IDs the user played (no hardcoded take limit)
        $quizIds = QuizUser::where('user_id', $userId)
            ->pluck('quiz_id')
            ->merge(
                \App\Models\TempQuizUser::where('user_id', $userId)->pluck('quiz_id')
            )
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        $tenantId = $request->header('X-Tenant-Id') ?? 1;
        $rawBanners = \App\Models\Banner::where('status', 'active')
            ->whereIn('tenant_id', [$tenantId, 1])
            ->latest('id')
            ->get();
        $banners = $rawBanners->map(function ($b) {
            return [
                'id' => $b->id,
                'name' => $b->name,
                'banner_url' => $b->banner_url,
                'image' => $b->banner_url,
                'url' => $b->url,
                'type' => $b->type ?? ['all'],
            ];
        });

        if (empty($quizIds)) {
            return response()->json([
                'success' => true,
                'histories' => [],
                'banners' => $banners,
                'has_more' => false,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => 0,
            ]);
        }

        $baseQuery = Quiz::select(['id', 'tenant_id', 'category_id', 'title', 'complete_status', 'updated_at'])
            ->whereIn('id', $quizIds)
            ->where('complete_status', 'completed');

        $totalCount = $baseQuery->count();

        $pageQuizzes = $baseQuery
            ->with(['category:id,name,icon'])
            ->latest('updated_at')
            ->skip($offset)
            ->take($perPage)
            ->get();

        $pageQuizIds = $pageQuizzes->pluck('id')->toArray();

        $playedCountsQuiz = QuizUser::whereIn('quiz_id', $pageQuizIds)
            ->selectRaw('quiz_id, count(*) as count')
            ->groupBy('quiz_id')
            ->pluck('count', 'quiz_id');

        $playedCountsTemp = \App\Models\TempQuizUser::whereIn('quiz_id', $pageQuizIds)
            ->selectRaw('quiz_id, count(*) as count')
            ->groupBy('quiz_id')
            ->pluck('count', 'quiz_id');

        $histories = $pageQuizzes->map(function ($quiz) use ($playedCountsQuiz, $playedCountsTemp) {
            $totalCount = (int) (($playedCountsQuiz[$quiz->id] ?? 0) + ($playedCountsTemp[$quiz->id] ?? 0));
            $quiz->quiz_users_count = max(1, $totalCount);
            $quiz->played_count = max(1, $totalCount);
            return $quiz;
        });

        $hasMore = ($offset + $pageQuizzes->count()) < $totalCount;

        return response()->json([
            'success' => true,
            'histories' => $histories,
            'banners' => $banners,
            'has_more' => $hasMore,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $totalCount,
        ]);
    }

    public function getQuizLeaderboard(Request $request, $id)
    {
        return app(\App\Http\Controllers\androidapi\QuizesController::class)->getQuizLeaderboard($request, $id);
    }
}
