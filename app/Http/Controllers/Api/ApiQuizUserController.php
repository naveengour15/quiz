<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ApiQuizUserController extends Controller
{
    /**
     * Get all users with user_id, user_name, mobile, is_active.
     *
     * @param Request $request
     * @return JsonResponse
     */
    // public function users(Request $request): JsonResponse
    // {
        
    //     $users = User::query()
    //         ->select([
    //             'id as user_id',
    //             'name as user_name',
    //             'mobile',
    //             'is_active',
    //         ])
    //         ->where('updated_at', '>=', now()->subMinutes(30))
    //         ->latest('id')->get();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Users retrieved successfully.',
    //         'total'   => $users->count(),
    //         'data'    => $users,
    //     ]);
    // }


    // public function users(Request $request): JsonResponse
    // {
    //     // ✅ 2 Alag Variables: Dono tables ke liye independent time control
    //     $userTimeAgo = now()->subMinutes(30);       // Users table ke liye time limit
    //     $notifyTimeAgo = now()->subMinutes(30);     // WhatsApp notifies table ke liye time limit

    //     $users = DB::table('users')
    //         ->leftJoin('users_whatsapp_notifies', 'users.id', '=', 'users_whatsapp_notifies.user_id')
    //         ->select(
    //             'users.id as user_id',
    //             'users.name as user_name',
    //             'users.mobile',
    //             DB::raw("
    //                 CASE 
    //                     WHEN users.is_active = 0 THEN 0
    //                     WHEN users.is_active = 1 AND users_whatsapp_notifies.status IN ('inactive', '0', 0) THEN 0                             
    //                     ELSE users.is_active
    //                 END as is_active
    //             ")
    //         )
    //         // ✅ UPDATED: Dono tables apne respective time variables se check honge
    //         ->where(function ($query) use ($userTimeAgo, $notifyTimeAgo) {
    //             $query->where('users.updated_at', '>=', $userTimeAgo)
    //                 ->orWhere('users_whatsapp_notifies.updated_at', '>=', $notifyTimeAgo);
    //         })
    //         ->latest('users.id')
    //         ->get();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Users retrieved successfully.',
    //         'total'   => $users->count(),
    //         'data'    => $users,
    //     ]);
    // }

    public function users(Request $request): JsonResponse
    {
        // ==========================================
        // 1. Time Variables (30 Minutes)
        // ==========================================
        // $userTimeAgo = now()->subMinutes(30);
        $userTimeAgo = now()->subDays(2);
        $notifyTimeAgo = now()->subMinutes(30);

        // ==========================================
        // 2. Fetch Recent Users (Last 30 Mins)
        // ==========================================
        $recentUsers = DB::table('users')
            ->leftJoin('users_whatsapp_notifies', 'users.id', '=', 'users_whatsapp_notifies.user_id')
            ->select(
                'users.id as user_id',
                'users.name as user_name',
                'users.mobile',
                DB::raw("
                    CASE
                        WHEN users.is_active = 0 THEN 0
                        WHEN users.is_active = 1 AND users_whatsapp_notifies.status IN ('inactive', '0', 0) THEN 0
                        ELSE users.is_active
                    END as is_active
                ")
            )
            ->where(function ($query) use ($userTimeAgo, $notifyTimeAgo) {
                $query->where('users.updated_at', '>=', $userTimeAgo)
                    ->orWhere('users_whatsapp_notifies.updated_at', '>=', $notifyTimeAgo);
            })
            ->latest('users.id')
            ->get()
            ->keyBy('user_id');

        // ==========================================
        // 3. Call Separate Function for 15-Day Quiz Inactivity
        // ==========================================
        $inactiveQuizUsers = $this->getInactiveQuizUsers(20);

        // ==========================================
        // 4. Merge Logic
        // ==========================================
        foreach ($inactiveQuizUsers as $inactiveUser) {
            if ($recentUsers->has($inactiveUser->user_id)) {
                // Agar user list me hai, toh status 0 kardo
                $recentUsers[$inactiveUser->user_id]->is_active = 0;
            } else {
                // Agar user list me nahi hai, toh add kardo with status 0
                $recentUsers->put($inactiveUser->user_id, (object) [
                    'user_id'   => $inactiveUser->user_id,
                    'user_name' => $inactiveUser->user_name,
                    'mobile'    => $inactiveUser->mobile,
                    'is_active' => 0,
                ]);
            }
        }

        // ==========================================
        // 5. Sort by user_id (Descending) and Re-index
        // ==========================================
        $finalUsers = $recentUsers->sortByDesc('user_id')->values();

        // ==========================================
        // 6. Response
        // ==========================================
        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'total'   => $finalUsers->count(),
            'data'    => $finalUsers,
        ]);
    }

    private function getInactiveQuizUsers(int $days = 20): Collection
    {
        $cutoffDate = now()->subDays($days);

        return DB::table('users')
            ->select(
                'users.id as user_id',
                'users.name as user_name',
                'users.mobile'
            )
            // ✅ NEW LINE: Sirf un users ko check karo jinki joining date $days se PURANI hai.
            // Isse "new aya he" wale users is list me nahi aayenge aur unka original status (1) safe rahega.
            ->where('users.created_at', '<', $cutoffDate) 
            
            ->whereNotExists(function ($query) use ($cutoffDate) {
                $query->select(DB::raw(1))
                    ->from('quiz_users')
                    ->whereColumn('quiz_users.user_id', 'users.id')
                    ->where('quiz_users.created_at', '>=', $cutoffDate); 
            })
            ->get();
    }

    /**
     * Get the latest upcoming quiz that has 20 minutes or less remaining until start.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function latestUpcomingQuiz_old(Request $request): JsonResponse
    {
        $now = now();
        $twentyMinutesLater = now()->addMinutes(20);

        $query = Quiz::query()
            ->where('complete_status', 'pending')
            ->where('status', 'scheduled')
            ->where('approve_status', 'approved')
            ->where(function ($q) use ($now, $twentyMinutesLater) {
                $q->where(function ($sub) use ($now, $twentyMinutesLater) {
                    $sub->whereNotNull('scheduled_at')
                        ->where('scheduled_at', '<=', $twentyMinutesLater)
                        ->where('scheduled_at', '>=', $now);
                })->orWhere(function ($sub) use ($now, $twentyMinutesLater) {
                    $sub->whereNull('scheduled_at')
                        ->whereNotNull('availability_start')
                        ->where('availability_start', '<=', $twentyMinutesLater)
                        ->where('availability_start', '>=', $now);
                });
            });

        $quiz = $query->orderBy('scheduled_at', 'asc')->latest('id')->first();

        if (!$quiz) {
            return response()->json([
                'success' => false,
                'message' => 'No upcoming quiz found with 20 minutes or less remaining.',
                'data' => null,
            ], 404);
        }

        $scheduleTime = $quiz->scheduled_at ?? $quiz->availability_start ?? $now;
        $scheduleCarbon = Carbon::parse($scheduleTime);
        $sendDateTime = $scheduleCarbon->copy()->subMinutes(20);

        $data = [
            'tenant_id' => (int) ($quiz->tenant_id ?? 1),
            'quiz_id' => (int) $quiz->id,
            'quiz_name' => (string) ($quiz->title ?? ''),
            'schedule_time' => $scheduleCarbon->format('Y-m-d H:i:s'),
            'send_date_time' => $sendDateTime->format('Y-m-d H:i:s'),
            'status' => (string) ($quiz->complete_status ?? 'pending'),
            'url' => $quiz->id . '?auto_join=1',
        ];

        return response()->json([
            'success' => true,
            'message' => 'Latest upcoming quiz retrieved successfully.',
            'data' => $data,
        ]);
    }


    public function latestUpcomingQuiz(Request $request): JsonResponse
    {
        $now = now();

        $quizzes = Quiz::query()
            ->withTrashed()
            ->where('complete_status', 'pending')
            ->where('approve_status', 'approved')
            ->where('scheduled_at', '>=', $now)
            ->orderBy('scheduled_at', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        if ($quizzes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No upcoming quiz found.',
                'data' => [],
            ], 404);
        }

        $data = $quizzes->map(function ($quiz) {

            $scheduleTime = $quiz->scheduled_at
                ?? $quiz->availability_start
                ?? now();

            $whatsapp_message_sent_before_minutes = $quiz->whatsapp_message_sent_before_minutes ?? 12;

            $scheduleCarbon = Carbon::parse($scheduleTime);
            $sendDateTime = $scheduleCarbon->copy()->subMinutes($whatsapp_message_sent_before_minutes);

            return [
                'tenant_id' => (int) ($quiz->tenant_id ?? 1),
                'quiz_id' => (int) $quiz->id,
                'quiz_name' => (string) ($quiz->title ?? ''),
                'schedule_time' => $scheduleCarbon->format('Y-m-d H:i:s'),
                'quiz_start_time' => $scheduleCarbon->format('h:i A'),
                'send_date_time' => $sendDateTime->format('Y-m-d H:i:s'),
                'status' => (string) ($quiz->complete_status ?? 'pending'),
                'url' => $quiz->id . '?auto_join=1',
                'quiz_deleted' => $quiz->deleted_at ? 'deleted' : 'no',
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Upcoming quizzes retrieved successfully.',
            'data' => $data,
        ]);
    }
}
