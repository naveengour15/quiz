<?php

namespace App\Http\Controllers\androidapi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\androidapi\Traits\ResolvesAndroidUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    use ResolvesAndroidUser;

    public function getFeedbackHistory(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $feedbacks = DB::table('quiz_user_feedback')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $tenantId = $request->header('X-Tenant-Id') ?? ($user->tenant_id ?? 1);
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

        return response()->json([
            'success' => true,
            'feedbacks' => $feedbacks,
            'banners' => $banners,
        ]);
    }

    public function submitFeedback(Request $request)
    {
        try {
            $user = $this->getAuthUser($request);
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $request->validate([
                'subject' => 'required|string|max:191',
                'message' => 'required|string|max:1000',
            ]);

            DB::table('quiz_user_feedback')->insert([
                'tenant_id'    => $user->tenant_id ?? 1,
                'user_id'      => $user->id,
                'subject'      => $request->subject,
                'message'      => $request->message,
                'status'       => 'pending',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you! Your feedback has been submitted successfully.'
            ]);

        } catch (\Throwable $e) {
            Log::error('Quiz Feedback Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }
}
