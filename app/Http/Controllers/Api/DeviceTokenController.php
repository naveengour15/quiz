<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Services\FirebaseNotificationService;
use App\Http\Controllers\androidapi\Traits\ResolvesAndroidUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DeviceTokenController extends Controller
{
    use ResolvesAndroidUser;

    /**
     * Register or Update FCM Device Token and Auto-Subscribe to Topic
     */
    public function register(Request $request, FirebaseNotificationService $firebaseService)
    {
        $validator = Validator::make($request->all(), [
            'token'    => 'required|string',
            'platform' => 'nullable|string|max:16',
            'device_id'=> 'nullable|string|max:191',
            'model'    => 'nullable|string|max:128',
            'brand'    => 'nullable|string|max:64',
            'topic'    => 'nullable|string|max:64',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $authUser = $this->getAuthUser($request);
        $userId = $authUser ? $authUser->id : ($request->input('user_id', 0) ?: 0);
        $token = trim((string) $request->input('token'));
        $platform = strtolower((string) ($request->input('platform') ?? 'android'));
        $targetTopic = $request->input('topic') ?: config('firebase.topic', env('FIREBASE_TOPIC', 'quiz_updates'));

        $deviceToken = DeviceToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id'      => $userId ?: 0,
                'platform'     => $platform,
                'device_id'    => $request->input('device_id'),
                'brand'        => $request->input('brand'),
                'manufacturer' => $request->input('manufacturer'),
                'model'        => $request->input('model'),
                'device_name'  => $request->input('device_name'),
                'os_name'      => $request->input('os_name'),
                'os_version'   => $request->input('os_version'),
                'app_version'  => $request->input('app_version'),
                'is_active'    => true,
                'last_used_at' => now(),
            ]
        );

        // Auto-subscribe the device token to the FCM topic (e.g. quiz_updates)
        $topicResult = $firebaseService->subscribeToTopic($token, $targetTopic);

        return response()->json([
            'success' => true,
            'message' => 'Device token registered and subscribed to topic successfully',
            'data'    => [
                'id'               => $deviceToken->id,
                'is_active'        => $deviceToken->is_active,
                'user_id'          => $deviceToken->user_id,
                'platform'         => $deviceToken->platform,
                'topic'            => $targetTopic,
                'topic_subscribed' => $topicResult['success'] ?? false,
                'updated_at'       => $deviceToken->updated_at,
            ]
        ]);
    }

    /**
     * Deactivate a device token (e.g. on logout)
     */
    public function deactivate(Request $request)
    {
        $token = $request->input('token');
        if ($token) {
            DeviceToken::where('token', $token)->update(['is_active' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Device token deactivated successfully'
        ]);
    }
}
