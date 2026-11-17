<?php

namespace App\Repositories\api;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Interfaces\api\ProfileRepositoryInterface;

use App\Models\Leaderboard;
use App\Models\MobileVerification;
use App\Helpers\BuildEmailHelper;

class ProfileRepository implements ProfileRepositoryInterface
{
    public function getProfile($user): array
    { 
        
        $leaderboard = Leaderboard::where('user_id', $user->id)->where('status', 'active')->first();
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'role' => $user->role,
            'role_id' => $user->role_id,
            'alternative_mobile' => $user->alternative_mobile,
            'verify_mobile' => $user->verify_mobile,
            'verify_email' => $user->verify_email,
            'state' => $user->state,
            'city' => $user->city,
            'address' => $user->address,
            'gender' => $user->gender,
            'date_of_birth' => $user->date_of_birth,
            'favorite_color' => $user->favorite_color,
            'hobbies' => $user->hobbies,
            'games' => $user->games,
            'interest' => $user->interest,
            'profile_photo' => $user->profile_photo,
            'referral_code' => $user->referral_code,
            'referral_code_to' => $user->referral_code_to,
            'referral_user_id' => $user->referral_user_id,
            'create_quiz_limit' => $user->create_quiz_limit,
            'available_quiz_limit' => $user->available_quiz_limit,
            'quiz_leave' => $user->quiz_leave,
            'quiz_rank' => $user->quiz_rank,
            'wallet_balance' => $user->wallet_balance,
            'is_active' => $user->is_active,
            'can_login' => $user->can_login,
            'otp_enabled' => $user->otp_enabled,
            'session_token' => $user->session_token,
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'leaderboard_rank'=> $leaderboard?->rank ?? 0,
            'leader_board_point'=> $leaderboard?->points ?? 0,
        ];
        return [
            'success' => true,
            'user'    => $data,
        ];
    }

    public function updateProfile($user, array $data, ?UploadedFile $profilePhoto = null): array
    {
        try {

            if ($profilePhoto) {

                Log::info('Uploading profile photo', [
                    'user_id' => $user->id,
                ]);

                $filename = 'profile-photos/' . time() . '_' . $user->id . '.' . $profilePhoto->getClientOriginalExtension();

                Storage::disk('s3')->put(
                    $filename,
                    file_get_contents($profilePhoto),
                    'public'
                );

                $data['profile_photo'] = $filename;

                Log::info('Profile photo uploaded successfully', [
                    'user_id' => $user->id,
                    'path' => $filename,
                ]);
            }

            $user->fill($data);
            $user->save();

            return [
                'success' => true,
                'message' => 'Profile updated successfully.',
                'user' => $user->fresh(),
            ];

        } catch (\Exception $e) {

            Log::error('Profile update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update profile.',
            ];
        }
    }

    public function updateAlternativeMobile($user, string $mobile): array
    {
        $user->alternative_mobile = $mobile;
        $user->save();

        return [
            'success' => true,
            'message' => 'Alternative mobile updated successfully',
        ];
    }


    public function sendOtpVerifyEmail($user)
    {
        try {
            if($user->email != '') {
                // Logic for sending verification email
                $otp = random_int(100000, 999999);
                
                MobileVerification::updateOrCreate(
                    [
                        'mobile_number' => $user->mobile,
                    ],
                    [
                        'tenant_id' => $user->tenant_id,
                        'otp' => $otp,
                        'token' => (string) Str::uuid(),
                    ]
                );

                BuildEmailHelper::sendOtpMail($user->email, $otp);

                return [
                    'status' => true,
                    'code' => 200,
                    'otp' => $otp,
                    'message' => 'Verification email sent successfully.',
                ];
            } else {
                 return [
                    'status' => false,
                    'code' => 401,
                    'message' => 'Verification email not found.',
                ];
            }

        }catch (\Throwable $e) {

            Log::error('Send Verify Email API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return [
                'status' => false,
                'code' => 500,
                'message' => 'Something went wrong.',
            ];
        }
    }

    public function verifyEmailOtp($user, array $data): array
    {
         try {
                       
            $mobile = MobileVerification::where('mobile_number', $user->mobile)->where('otp', $data['otp'])->first();
            if($mobile) {
                $user->update([
                    'verify_email' => 1,
                ]);
                return [
                    'status' => true,
                    'code' => 200,
                    'message' => 'Verified email successfully.',
                    'data' => $user->fresh(),
                ];
            } else{
                   return [
                    'status' => false,
                    'code' => 401,
                   'message' => "Invalid Otp",
                ];
            }

        }catch (\Throwable $e) {

            Log::error('Update Verified email API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return [
                'status' => false,
                'code' => 500,
                'message' => 'Something went wrong.',
            ];
        }
    }

    public function deleteAccount($user): array
    {
        $user->can_login = 0;
        $user->is_active = 0;
        $user->save();

        return [
            'success' => true,
            'message' => 'Account deactivated successfully',
        ];
    }
}
