<?php

namespace App\Http\Controllers\androidapi;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\City;
use App\Models\User;
use App\Models\WalletHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ProfileController extends Controller
{
    /**
     * Resolve authenticated user from Guard, Bearer token, Session token, or fallback
     */
    private function getAuthenticatedUser(Request $request): ?User
    {
        if (Auth::check()) {
            return Auth::user();
        }

        $token = $request->bearerToken();
        if (!$token) {
            $token = $request->header('session_token') ?: $request->input('session_token');
        }

        if ($token) {
            $user = User::where('session_token', $token)->first();
            if ($user) return $user;
        }

        $userId = $request->input('user_id') ?: $request->header('user_id');
        if ($userId) {
            $user = User::find($userId);
            if ($user) return $user;
        }

        return null;
    }

    private function resolvePhotoUrl(?string $profilePhoto): ?string
    {
        if (empty($profilePhoto)) {
            return null;
        }

        if (str_starts_with($profilePhoto, 'http://') || str_starts_with($profilePhoto, 'https://') || str_starts_with($profilePhoto, 'data:image')) {
            return $profilePhoto;
        }

        $cleanPath = ltrim($profilePhoto, '/');
        try {
            return Storage::disk('s3')->url($cleanPath);
        } catch (\Exception $e) {
            $awsUrl = config('filesystems.disks.s3.url') ?: env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com');
            return rtrim($awsUrl, '/') . '/' . $cleanPath;
        }
    }

    public function getProfile(Request $request)
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => false,
                    'message' => 'Unauthorized user.'
                ], 401);
            }

            $states = [];
            try {
                $states = State::where('status', 'active')->orderBy('name')->get();
            } catch (\Exception $e) {
                try {
                    $states = State::orderBy('name')->get();
                } catch (\Exception $ex) {
                    $states = [];
                }
            }

            $photoUrl = $this->resolvePhotoUrl($user->profile_photo);

            return response()->json([
                'success' => true,
                'status' => true,
                'message' => 'Profile retrieved successfully.',
                'user' => $user,
                'photo_url' => $photoUrl,
                'states' => $states
            ]);
        } catch (\Exception $e) {
            Log::error('getProfile exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => 'Error retrieving profile: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => false,
                    'message' => 'Unauthorized user.'
                ], 401);
            }

            $request->validate([
                'name' => 'nullable|string|max:191',
                'player_name' => 'nullable|string|max:191',
                'city' => 'nullable|string|max:191',
                'email' => 'nullable|email|max:191',
                'alternative_mobile' => 'nullable|string|max:15',
                'state' => 'nullable|string|max:191',
                'address' => 'nullable|string|max:500',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other',
                'favorite_color' => 'nullable|string',
                'hobbies' => 'nullable|string',
                'games' => 'nullable|string',
                'interest' => 'nullable|string',
                'language' => 'nullable|in:en,hi,hinglish',
            ]);

            $formatChipField = function($value) {
                if (is_null($value) || $value === '' || $value === '[]') {
                    return null;
                }
                if (is_array($value)) {
                    return array_values(array_filter(array_map('trim', $value)));
                }
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (is_array($decoded)) {
                        return array_values(array_filter(array_map('trim', $decoded)));
                    }
                    return array_values(array_filter(array_map('trim', explode(',', $value))));
                }
                return null;
            };

            $updateData = [];
            foreach (['name', 'player_name', 'city', 'email', 'alternative_mobile', 'state', 'address', 'date_of_birth', 'gender', 'language'] as $field) {
                if ($request->has($field) && !is_null($request->input($field))) {
                    $updateData[$field] = $request->input($field);
                }
            }

            foreach (['favorite_color', 'hobbies', 'games', 'interest'] as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $formatChipField($request->input($field));
                }
            }

            if (empty($user->player_name) && !empty($request->name) && empty($updateData['player_name'])) {
                $updateData['player_name'] = $this->generateUniquePlayerName($request->name);
            }

            $photoInput = $request->input('profile_photo');
            if (!empty($photoInput) && is_string($photoInput) && str_starts_with($photoInput, 'data:image')) {
                try {
                    $imageParts = explode(';base64,', $photoInput);
                    if (count($imageParts) === 2) {
                        $imageBytes = base64_decode($imageParts[1]);
                        if ($imageBytes !== false) {
                            $imageName = uniqid(time(), true) . '.webp';
                            $path = "users/{$imageName}";

                            Storage::disk('s3')->put($path, $imageBytes, 'public');
                            $updateData['profile_photo'] = $path;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Base64 photo upload error to S3: ' . $e->getMessage());
                }
            } elseif ($request->hasFile('profile_photo')) {
                $file = $request->file('profile_photo');
                if ($file && $file->isValid()) {
                    $imageName = uniqid(time(), true) . '.webp';
                    $path = "users/{$imageName}";
                    $imageBytes = file_get_contents($file->getRealPath());

                    Storage::disk('s3')->put($path, $imageBytes, 'public');
                    $updateData['profile_photo'] = $path;
                }
            }

            if (!empty($updateData)) {
                $user->update($updateData);
                $user->refresh();
            }

            $rewardResult = $this->applyProfileCompletionReward($user);
            $user->refresh();

            $photoUrl = $this->resolvePhotoUrl($user->profile_photo);

            return response()->json([
                'success' => true,
                'status' => true,
                'message' => 'Profile updated successfully.',
                'user' => $user,
                'photo_url' => $photoUrl,
                'reward_given' => $rewardResult['reward_given'] ?? false,
                'reward_amount' => $rewardResult['reward_amount'] ?? 0,
                'profile_status' => $user->profile_status ?? 'pending'
            ]);
        } catch (\Exception $e) {
            Log::error('updateProfile exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateLanguage(Request $request)
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => false,
                    'message' => 'Unauthorized user.'
                ], 401);
            }

            $request->validate(['language' => 'required|in:en,hi,hinglish']);
            $user->update(['language' => $request->language]);

            return response()->json([
                'success' => true,
                'status' => true,
                'message' => 'Language updated successfully.',
                'language' => $user->language
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => 'Failed to update language: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStates(Request $request)
    {
        try {
            $states = State::where('status', 'active')->orderBy('name', 'asc')->get(['id', 'name']);
            return response()->json([
                'success' => true,
                'status' => true,
                'states' => $states
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => false,
                'states' => []
            ]);
        }
    }

    public function getCities(Request $request)
    {
        try {
            $stateParam = $request->state_id ?? $request->state;
            if (!$stateParam) {
                return response()->json([
                    'success' => false,
                    'status' => false,
                    'cities' => []
                ]);
            }

            if (!is_numeric($stateParam)) {
                $st = State::where('name', $stateParam)->first();
                $stateId = $st ? $st->id : null;
            } else {
                $stateId = (int)$stateParam;
            }

            if (!$stateId) {
                return response()->json([
                    'success' => false,
                    'status' => false,
                    'cities' => []
                ]);
            }

            $cities = City::where('state_id', $stateId)
                ->where('status', 'active')
                ->orderBy('name', 'asc')
                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'status' => true,
                'cities' => $cities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => false,
                'cities' => []
            ]);
        }
    }

    public function deleteAccount(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized user.'], 401);
        }

        $user->update(['is_active' => 0, 'can_login' => 0, 'session_token' => null]);
        return response()->json(['success' => true, 'message' => 'Account deleted successfully.']);
    }

    public function getProfileOptions()
    {
        try {
            // Database se sirf 'name' column fetch karenge jinka status 'active' ho
            $hobbies = \App\Models\QuizHobby::where('status', 'active')->pluck('name')->toArray();
            $games = \App\Models\QuizGame::where('status', 'active')->pluck('name')->toArray();
            $interests = \App\Models\QuizInterest::where('status', 'active')->pluck('name')->toArray();
            $colors = \App\Models\QuizColor::where('status', 'active')->pluck('name')->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'hobbies' => $hobbies,
                    'games' => $games,
                    'interests' => $interests,
                    'colors' => $colors
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('getProfileOptions exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch options: ' . $e->getMessage()
            ], 500);
        }
    }

    //     private function isProfileComplete(User $user): bool
    // {
    //     $requiredFields = [
    //         'name',
    //         'gender',
    //         'date_of_birth',
    //         'state',
    //         'city',
    //         'address',
    //     ];

    //     foreach ($requiredFields as $field) {
    //         if (blank($user->{$field})) {
    //             return false;
    //         }
    //     }

    //     return true;
    // }

    private function isProfileComplete(User $user): bool
    {
        // Normal required fields
        $requiredFields = [
            'alternative_mobile',
            'state',
            'city',
            'address',
            'date_of_birth',
            'gender',
            'name',
            'referral_code_to',
            'profile_photo',
        ];

        foreach ($requiredFields as $field) {
            if (blank($user->{$field})) {
                return false;
            }
        }

        // JSON / Array fields
        $arrayFields = [
            'favorite_color',
            'interest',
            'games',
            'hobbies',
        ];

        foreach ($arrayFields as $field) {
            $value = $user->{$field};

            // null / empty string
            if (blank($value)) {
                return false;
            }

            // JSON string "[]"
            if (is_string($value)) {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE && empty($decoded)) {
                    return false;
                }
            }

            // Actual empty array []
            if (is_array($value) && empty($value)) {
                return false;
            }
        }

        return true;
    }

    private function applyProfileCompletionReward(User $user): array
    {
        $previousStatus = $user->profile_status ?? 'pending';

        if ($previousStatus === 'complete' || $previousStatus === 'completed') {
            return [
                'reward_given' => false,
                'reward_amount' => 0,
                'profile_status' => 'complete',
            ];
        }

        $isComplete = $this->isProfileComplete($user);
        if (!$isComplete) {
            $user->profile_status = 'pending';
            $user->save();
            return [
                'reward_given' => false,
                'reward_amount' => 0,
                'profile_status' => 'pending',
            ];
        }

        $hasReward = WalletHistory::where('user_id', $user->id)
            ->where('transaction_type', 'Profile_Completed')
            ->exists();

        if ($hasReward) {
            $user->profile_status = 'complete';
            $user->save();
            return [
                'reward_given' => false,
                'reward_amount' => 0,
                'profile_status' => 'complete',
            ];
        }

           // ✅ 4. DYNAMIC REWARD AMOUNT (Quiz Settings se fetch karna)
        $setting = \App\Models\QuizSetting::first();
        $rewardAmount = (int) ($setting->profile_update_point ?? 0);

        // Safety Check: Agar admin ne 0 ya negative points set kiye hain, toh reward nahi denge
        if ($rewardAmount <= 0) {
            $user->profile_status = 'complete';
            $user->save();
            return [
                'reward_given' => false,
                'reward_amount' => 0,
                'profile_status' => 'complete',
            ];
        }

        $oldWalletBalance = (float) ($user->wallet_balance ?? 0);
        // $rewardAmount = 20;
        $newWalletBalance = $oldWalletBalance + $rewardAmount;

        $user->wallet_balance = $newWalletBalance;
        $user->profile_status = 'complete';
        $user->save();

        WalletHistory::create([
            'tenant_id' => $user->tenant_id ?? 1,
            'user_id' => $user->id,
            'quiz_id' => null,
            'referral_user_id' => null,
            'survey_id' => null,
            'old_wallet_balance' => $oldWalletBalance,
            'amount' => $rewardAmount,
            'new_wallet_balance' => $newWalletBalance,
            'type' => 'credit',
            'transaction_type' => 'Profile_Completed',
            'reference_id' => 'profile-completion-' . $user->id,
            // 'remarks' => 'Profile Completed Reward - 20 Points',
            //  // ✅ Remarks me bhi dynamic amount dikhayega
            'remarks' => 'Profile Completed Reward - ' . $rewardAmount . ' Points', 
        ]);

        return [
            'reward_given' => true,
            'reward_amount' => $rewardAmount,
            'profile_status' => 'complete',
            'new_wallet_balance' => $newWalletBalance,
        ];
    }

    private function generateUniquePlayerName(string $userName): string
    {
        $prefix = strtoupper(str_pad(substr($userName, 0, 3), 3, 'X'));

        do {
            $randomSuffix = str_pad((string) rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $playerName = $prefix . $randomSuffix;
        } while (User::where('player_name', $playerName)->exists());

        return $playerName;
    }
}
