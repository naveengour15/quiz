<?php

namespace App\Repositories\androidapi;

use App\Interfaces\androidapi\AuthRepositoryInterface;
use App\Models\User;
use App\Models\Tenant;
use App\Models\MobileVerification;
use App\Models\ReferralCode;
use App\Models\State;
use App\Repositories\WhatsAppRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthRepository implements AuthRepositoryInterface
{
    /**
     * Send OTP to user's mobile number via WhatsApp / SMS.
     */
    public function sendOtp(string $mobile): array
    {
        try {
            $user = User::where('mobile', $mobile)->first();

            if ($user && !$user->isAllowedToLogin()) {
                return [
                    'status' => false,
                    'code' => 403,
                    'message' => 'Your account is not activated for login. Please contact administrator.',
                ];
            }

            $otp = (string) rand(1000, 9999);

            MobileVerification::updateOrCreate(
                ['mobile_number' => $mobile],
                [
                    'tenant_id' => $user ? $user->tenant_id : 1,
                    'otp' => $otp,
                    'token' => (string) Str::uuid(),
                ]
            );

            $sentViaWhatsApp = false;
            try {
                $whatsAppMobile = str_starts_with($mobile, '91') ? $mobile : '91' . $mobile;
                $tenant = Tenant::first() ?? Tenant::find(1);

                $parameters = [
                    ['name' => 'otp', 'value' => $otp],
                    ['name' => 'company_name', 'value' => 'FsrQuiz'],
                ];

                $buttonParameters = [
                    ['index' => 0, 'value' => 'verify'],
                ];

                $whatsAppRepo = app(WhatsAppRepositoryInterface::class);
                $response = $whatsAppRepo->sendWhatsAppMessage(
                    $whatsAppMobile,
                    'CHALO_OTP',
                    $parameters,
                    $buttonParameters,
                    $tenant
                );

                if ($response) {
                    $sentViaWhatsApp = true;
                    Log::info("WhatsApp OTP sent to {$mobile}: {$otp}");
                }
            } catch (\Throwable $e) {
                Log::error("AndroidAPI - Failed to send WhatsApp OTP to {$mobile}: " . $e->getMessage());
            }

            return [
                'status' => true,
                'message' => $sentViaWhatsApp
                    ? "OTP sent successfully via WhatsApp to +91 {$mobile}."
                    : "OTP generated for +91 {$mobile}.",
                'data' => [
                    'mobile' => $mobile,
                    'otp' => $otp,
                    'is_existing_user' => ($user && !empty($user->name)),
                    'sent_via_whatsapp' => $sentViaWhatsApp
                ]
            ];
        } catch (\Throwable $e) {
            Log::error("AndroidAPI - sendOtp Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Failed to send OTP. Please try again later.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify OTP and return user auth data.
     */
    public function verifyOtp(string $mobile, string $otp, string $loginType = 'android'): array
    {
        try {
            $mobile = trim((string)$mobile);
            $otp = trim((string)$otp);

            $verification = MobileVerification::where('mobile_number', $mobile)
                ->orderBy('updated_at', 'desc')
                ->first();

            if (!$verification || trim((string)$verification->otp) !== $otp) {
                return [
                    'status' => false,
                    'message' => 'Invalid OTP entered. Please check and try again.'
                ];
            }

            // OTP verified -> delete verification record
            $verification->delete();

            $user = User::where('mobile', $mobile)->first();

            if ($user && !$user->isAllowedToLogin()) {
                return [
                    'status' => false,
                    'code' => 403,
                    'message' => 'Your account is not activated for login. Please contact administrator.',
                ];
            }

            if (!$user) {
                // Create user shell
                $user = User::create([
                    'tenant_id' => 1,
                    'mobile' => $mobile,
                    'login_type' => $loginType,
                    'role' => 'user',
                    'is_active' => 1,
                    'can_login' => 1,
                    'wallet_balance' => 0,
                    'session_token' => Str::random(60),
                    'password' => Hash::make(Str::random(16)),
                    'last_login_at' => now(),
                ]);
            } else {
                if (empty($user->session_token)) {
                    $user->session_token = Str::random(60);
                }
                $user->login_type = $loginType;
                $user->last_login_at = now();
                $user->save();
            }

            // The Android API uses the indexed session token consistently across
            // web and mobile clients; returning a Passport token here would not
            // be accepted by the lightweight API-token middleware.
            $token = $user->session_token;

            $isProfileComplete = !empty($user->name);

            return [
                'status' => true,
                'message' => 'OTP verified successfully.',
                'data' => [
                    'token' => $token,
                    'session_token' => $user->session_token,
                    'is_profile_complete' => $isProfileComplete,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'mobile' => $user->mobile,
                        'email' => $user->email,
                        'city' => $user->city,
                        'profile_photo' => $user->profile_photo ? asset($user->profile_photo) : null,
                        'wallet_balance' => (float) ($user->wallet_balance ?? 0),
                    ]
                ]
            ];
        } catch (\Throwable $e) {
            Log::error("AndroidAPI - verifyOtp Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'OTP verification failed.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Resend OTP to user's mobile number.
     */
    public function resendOtp(string $mobile): array
    {
        return $this->sendOtp($mobile);
    }

    /**
     * Complete or update profile for a user.
     */
  
    // public function completeProfile(int $userId, array $data): array
    // {
    //     try {
    //         $user = User::find($userId);
    //         if (!$user) {
    //             return [
    //                 'status' => false,
    //                 'message' => 'User not found.'
    //             ];
    //         }

    //         $updateData = [];

    //         if (!empty($data['name'])) {
    //             $updateData['name'] = trim($data['name']);
    //         }
    //         if (!empty($data['email'])) {
    //             $updateData['email'] = trim($data['email']);
    //         }
    //         if (!empty($data['gender'])) {
    //             $updateData['gender'] = $data['gender'];
    //         }
            
    //         $dateOfBirth = $data['date_of_birth'] ?? $data['dob'] ?? null;
    //         if (!empty($dateOfBirth)) {
    //             $updateData['date_of_birth'] = $dateOfBirth;
    //         }
    //         if (!empty($data['language'])) {
    //             $updateData['language'] = $data['language'];
    //         }

    //         // ==========================================
    //         // ✅ UPDATED REFERRAL CODE LOGIC
    //         // ==========================================
    //         if (!empty($data['referral_code'])) {
    //             $code = trim($data['referral_code']);
                
    //             // 1. Check karo ki code User table mein hai ya Admin table mein
    //             $referrerUser = User::where('referral_code', $code)->first();
    //             $referrerAdmin = \Illuminate\Support\Facades\DB::table('admin_users')->where('referral_code', $code)->first();
                
    //             if (!$referrerUser && !$referrerAdmin) {
    //                 return [
    //                     'status' => false,
    //                     'message' => 'Invalid Referral Code. Please check and try again.'
    //                 ];
    //             }
                
    //             $updateData['referral_code_to'] = $code;
                
                
    //             // 2. Referrer ka ID aur Code Type decide karo
    //             $referrerUserId = null;
    //             $isSuperAdminCode = false;

    //             if ($referrerUser) {
    //                 // Normal User ka code hai
    //                 $referrerUserId = $referrerUser->id;
    //                 $isSuperAdminCode = false;
    //             } elseif ($referrerAdmin) {
    //                 // Admin ka code hai (Specifically SANDE687 check)
    //                 if (strtoupper($code) === 'SANDE687') {
    //                     $isSuperAdminCode = true;
    //                     // Note: Yahan $referrerAdmin->user_id ya $referrerAdmin->id use karein, 
    //                     // jo bhi aapke admin_users table mein user ID se map karta hai.
    //                     $referrerUserId = $referrerAdmin->user_id ?? $referrerAdmin->id; 
    //                 }
    //             }

    //             // 3. Database mein Referral Record Save karna
    //             if ($referrerUserId) {
    //                 if ($isSuperAdminCode) {
    //                     // ✅ Super Admin Code (SANDE687): Sirf entry, no points, used = 'used'
    //                     \App\Models\ReferralCode::create([
    //                         'tenant_id' => $user->tenant_id ?? 1,
    //                         'user_id' => $referrerUserId,          // Jiska code use kiya (Admin)
    //                         'code' => $code,
    //                         'status' => 'active',
    //                         'used' => 'used',                      // Super admin ke liye turant 'used'
    //                         'referral_user_id' => $user->id,       // Jo abhi register/profile complete kar raha hai
    //                         'used_date' => now(),
    //                     ]);
    //                 } else {
    //                     // ✅ Normal User Code: Points denge, used = 'pending'
    //                     \App\Models\ReferralCode::create([
    //                         'tenant_id' => $user->tenant_id ?? 1,
    //                         'user_id' => $referrerUserId,          // Jiska code use kiya (Normal User)
    //                         'code' => $code,
    //                         'status' => 'active',
    //                         'used' => 'pending',                   // Normal user ke liye 'pending' rahega jab tak condition puri na ho
    //                         'points' => 10,
    //                         'referral_point_provide' => 'yes',
    //                         'referral_user_id' => $user->id,       // Jo abhi register/profile complete kar raha hai
    //                     ]);
    //                 }
    //             }
    //         }
    //         // ==========================================

    //         if (!empty($data['city'])) {
    //             $updateData['city'] = $data['city'];
    //         }
    //         if (!empty($data['state'])) {
    //             $state = is_numeric($data['state'])
    //                 ? State::find((int) $data['state'])
    //                 : null;
    //             $updateData['state'] = $state?->name ?? trim((string) $data['state']);
    //         }

    //         // Handle Profile Image Upload
    //         if (isset($data['profile_photo']) && $data['profile_photo'] instanceof \Illuminate\Http\UploadedFile) {
    //             $file = $data['profile_photo'];
    //             $filename = time() . '_' . Str::slug($user->name ?: 'user') . '.' . $file->getClientOriginalExtension();
    //             $destinationPath = public_path('uploads/profiles');
    //             if (!file_exists($destinationPath)) {
    //                 mkdir($destinationPath, 0755, true);
    //             }
    //             $file->move($destinationPath, $filename);
    //             $updateData['profile_photo'] = 'uploads/profiles/' . $filename;
    //         } elseif (isset($data['profile_photo']) && is_string($data['profile_photo']) && str_starts_with($data['profile_photo'], 'data:image')) {
    //             try {
    //                 $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $data['profile_photo']);
    //                 $decoded = base64_decode($base64);
    //                 $filename = time() . '_' . Str::slug($user->name ?: 'user') . '.jpg';
    //                 $destinationPath = public_path('uploads/profiles');
    //                 if (!file_exists($destinationPath)) {
    //                     mkdir($destinationPath, 0755, true);
    //                 }
    //                 file_put_contents($destinationPath . '/' . $filename, $decoded);
    //                 $updateData['profile_photo'] = 'uploads/profiles/' . $filename;
    //             } catch (\Throwable $e) {
    //                 Log::error('Error saving base64 profile photo: ' . $e->getMessage());
    //             }
    //         } elseif (isset($data['profile_photo']) && is_string($data['profile_photo']) && !empty($data['profile_photo'])) {
    //             $updateData['profile_photo'] = $data['profile_photo'];
    //         }

    //         // Generate Referral Code for user if not exists
    //         if (empty($user->referral_code)) {
    //             $nameToUse = !empty($data['name']) ? trim($data['name']) : ($user->name ?? 'USR');
    //             $cleanName = preg_replace('/[^a-zA-Z]/', '', $nameToUse);
    //             $namePrefix = strtoupper(substr($cleanName, 0, 3));
    //             if (strlen($namePrefix) < 3) {
    //                 $namePrefix = str_pad($namePrefix, 3, 'X');
    //             }
                
    //             $refCode = $namePrefix . rand(10000, 99999);
    //             $updateData['referral_code'] = $refCode;
    //             $updateData['referral_user_id'] = $referrerUserId;

    //             \App\Models\ReferralCode::firstOrCreate(
    //                 ['referral_user_id' => $user->id],
    //                 [
    //                     'tenant_id' => $user->tenant_id ?? 1,
    //                     'code' => $refCode,
    //                     'status' => 'active',
    //                 ]
    //             );
    //         }

    //         // ==========================================
    //         // ✅ DIRECT FETCH: Quiz Settings se limits layenge
    //         // ==========================================
    //         $settings = \App\Models\QuizSetting::first();
            
    //         // User table mein entry ke liye add karo (Fallback 0 agar setting na mile)
    //         $updateData['available_quiz_limit'] = $settings ? (int) $settings->user_quiz_limit : 0;
    //         $updateData['user_referral_limit']  = $settings ? (int) $settings->user_referral_limit : 0;
    //         $updateData['create_quiz_limit']  =  4;
    //         // ==========================================

    //         $user->update($updateData);
    //         $user->refresh();

    //         return [
    //             'status' => true,
    //             'message' => 'Profile updated successfully.',
    //             'data' => [
    //                 'user' => [
    //                     'id' => $user->id,
    //                     'name' => $user->name,
    //                     'mobile' => $user->mobile,
    //                     'email' => $user->email,
    //                     'city' => $user->city,
    //                     'state' => $user->state,
    //                     'gender' => $user->gender,
    //                     'date_of_birth' => $user->date_of_birth,
    //                     'dob' => $user->date_of_birth,
    //                     'language' => $user->language ?? 'en',
    //                     'referral_code_to' => $user->referral_code_to,
    //                     'profile_photo' => $user->profile_photo ? asset($user->profile_photo) : null,
    //                     'referral_code' => $user->referral_code,
    //                     'wallet_balance' => (float) ($user->wallet_balance ?? 0),
    //                 ]
    //             ]
    //         ];
    //     } catch (\Throwable $e) {
    //         Log::error("AndroidAPI - completeProfile Error: " . $e->getMessage());
    //         return [
    //             'status' => false,
    //             'message' => 'Failed to update profile.',
    //             'error' => $e->getMessage()
    //         ];
    //     }
    // }

    public function completeProfile(int $userId, array $data): array
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return [
                    'status' => false,
                    'message' => 'User not found.'
                ];
            }

            if (!$user->isAllowedToLogin()) {
                return [
                    'status' => false,
                    'code' => 403,
                    'message' => 'Your account is not activated for login. Please contact administrator.'
                ];
            }

            $updateData = [];

            if (!empty($data['name'])) {
                $updateData['name'] = trim($data['name']);
            }
            if (!empty($data['email'])) {
                $updateData['email'] = trim($data['email']);
            }
            if (!empty($data['gender'])) {
                $updateData['gender'] = $data['gender'];
            }
            
            $dateOfBirth = $data['date_of_birth'] ?? $data['dob'] ?? null;
            if (!empty($dateOfBirth)) {
                $updateData['date_of_birth'] = $dateOfBirth;
            }
            if (!empty($data['language'])) {
                $updateData['language'] = $data['language'];
            }

            // ==========================================
            // ✅ UPDATED REFERRAL CODE LOGIC (No Duplicates)
            // ==========================================
            if (!empty($data['referral_code'])) {
                $code = trim($data['referral_code']);
                
                // 🛡️ CHECK 1: Kya is user ne pehle se koi referral code apply kar rakha hai?
                $alreadyHasReferral = \App\Models\ReferralCode::where('referral_user_id', $user->id)->exists();
                
                // Agar pehle se nahi hai, tabhi aage badho
                if (!$alreadyHasReferral && empty($user->referral_code_to)) {
                    
                    // 1. Check karo ki code User table mein hai ya Admin table mein
                    $referrerUser = User::where('referral_code', $code)->first();
                    $referrerAdmin = \Illuminate\Support\Facades\DB::table('admin_users')->where('referral_code', $code)->first();
                    
                    if (!$referrerUser && !$referrerAdmin) {
                        return [
                            'status' => false,
                            'message' => 'Invalid Referral Code. Please check and try again.'
                        ];
                    }
                    
                    $updateData['referral_code_to'] = $code;
                    
                    // 2. Referrer ka ID aur Code Type decide karo
                    $referrerUserId = null;
                    $isSuperAdminCode = false;

                    if ($referrerUser) {
                        $referrerUserId = $referrerUser->id;
                        $isSuperAdminCode = false;
                    } elseif ($referrerAdmin) {
                        if (strtoupper($code) === 'SANDE687') {
                            $isSuperAdminCode = true;
                            $referrerUserId = $referrerAdmin->user_id ?? $referrerAdmin->id; 
                        }
                    }

                    // 3. Database mein Referral Record Save karna
                    if ($referrerUserId) {
                        if ($isSuperAdminCode) {
                            \App\Models\ReferralCode::create([
                                'tenant_id' => $user->tenant_id ?? 1,
                                'user_id' => $referrerUserId,
                                'code' => $code,
                                'status' => 'active',
                                'used' => 'used',
                                'referral_user_id' => $user->id,
                                'used_date' => now(),
                            ]);
                        } else {
                            \App\Models\ReferralCode::create([
                                'tenant_id' => $user->tenant_id ?? 1,
                                'user_id' => $referrerUserId,
                                'code' => $code,
                                'status' => 'active',
                                'used' => 'pending',
                                'points' => 10,
                                'referral_point_provide' => 'yes',
                                'referral_user_id' => $user->id,
                            ]);
                        }
                    }
                }
            }
            // ==========================================

            if (!empty($data['city'])) {
                $updateData['city'] = $data['city'];
            }
            if (!empty($data['state'])) {
                $state = is_numeric($data['state'])
                    ? State::find((int) $data['state'])
                    : null;
                $updateData['state'] = $state?->name ?? trim((string) $data['state']);
            }

            // Handle Profile Image Upload
            if (isset($data['profile_photo']) && $data['profile_photo'] instanceof \Illuminate\Http\UploadedFile) {
                $file = $data['profile_photo'];
                $filename = time() . '_' . Str::slug($user->name ?: 'user') . '.' . $file->getClientOriginalExtension();
                $destinationPath = public_path('uploads/profiles');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                $updateData['profile_photo'] = 'uploads/profiles/' . $filename;
            } elseif (isset($data['profile_photo']) && is_string($data['profile_photo']) && str_starts_with($data['profile_photo'], 'data:image')) {
                try {
                    $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $data['profile_photo']);
                    $decoded = base64_decode($base64);
                    $filename = time() . '_' . Str::slug($user->name ?: 'user') . '.jpg';
                    $destinationPath = public_path('uploads/profiles');
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
                    file_put_contents($destinationPath . '/' . $filename, $decoded);
                    $updateData['profile_photo'] = 'uploads/profiles/' . $filename;
                } catch (\Throwable $e) {
                    Log::error('Error saving base64 profile photo: ' . $e->getMessage());
                }
            } elseif (isset($data['profile_photo']) && is_string($data['profile_photo']) && !empty($data['profile_photo'])) {
                $updateData['profile_photo'] = $data['profile_photo'];
            }

            // Generate Referral Code for user if not exists
            if (empty($user->referral_code)) {
                $nameToUse = !empty($data['name']) ? trim($data['name']) : ($user->name ?? 'USR');
                $cleanName = preg_replace('/[^a-zA-Z]/', '', $nameToUse);
                $namePrefix = strtoupper(substr($cleanName, 0, 3));
                if (strlen($namePrefix) < 3) {
                    $namePrefix = str_pad($namePrefix, 3, 'X');
                }
                
                $refCode = $namePrefix . rand(10000, 99999);
                $updateData['referral_code'] = $refCode;
                 $updateData['referral_user_id'] = $referrerUserId;
                
                // ❌ REMOVED: $updateData['referral_user_id'] = $referrerUserId; 
                // (Yeh line undefined variable error deti thi agar user ne koi referral code input nahi kiya tha)

                \App\Models\ReferralCode::firstOrCreate(
                    ['referral_user_id' => $user->id],
                    [
                        'tenant_id' => $user->tenant_id ?? 1,
                        'code' => $refCode,
                        'status' => 'active',
                    ]
                );
            }

            // ==========================================
            // ✅ DIRECT FETCH: Quiz Settings se limits layenge
            // ==========================================
            $settings = \App\Models\QuizSetting::first();
            $updateData['available_quiz_limit'] = $settings ? (int) $settings->user_quiz_limit : 0;
            $updateData['user_referral_limit']  = $settings ? (int) $settings->user_referral_limit : 0;
            $updateData['create_quiz_limit']    = 4;
            // ==========================================

            $user->update($updateData);
            $user->refresh();

            // ✅ RESPONSE STRUCTURE EXACTLY SAME AS YOURS
            return [
                'status' => true,
                'message' => 'Profile updated successfully.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'mobile' => $user->mobile,
                        'email' => $user->email,
                        'city' => $user->city,
                        'state' => $user->state,
                        'gender' => $user->gender,
                        'date_of_birth' => $user->date_of_birth,
                        'dob' => $user->date_of_birth,
                        'language' => $user->language ?? 'en',
                        'referral_code_to' => $user->referral_code_to,
                        'profile_photo' => $user->profile_photo ? asset($user->profile_photo) : null,
                        'referral_code' => $user->referral_code,
                        'wallet_balance' => (float) ($user->wallet_balance ?? 0),
                    ]
                ]
            ];
        } catch (\Throwable $e) {
            Log::error("AndroidAPI - completeProfile Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Failed to update profile.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get profile data for the user.
     */
    public function getProfile(int $userId): array
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return [
                    'status' => false,
                    'message' => 'User not found.'
                ];
            }

            if (!$user->isAllowedToLogin()) {
                return [
                    'status' => false,
                    'code' => 403,
                    'message' => 'Your account is not activated for login. Please contact administrator.'
                ];
            }

            return [
                'status' => true,
                'message' => 'Profile details fetched successfully.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'mobile' => $user->mobile,
                        'email' => $user->email,
                        'city' => $user->city,
                        'state' => $user->state,
                        'gender' => $user->gender,
                        'date_of_birth' => $user->date_of_birth,
                        'profile_photo' => $user->profile_photo ? asset($user->profile_photo) : null,
                        'referral_code' => $user->referral_code,
                        'wallet_balance' => (float) ($user->wallet_balance ?? 0),
                        'language' => $user->language ?? 'en',
                    ]
                ]
            ];
        } catch (\Throwable $e) {
            Log::error("AndroidAPI - getProfile Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Failed to fetch profile.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Logout user.
     */
    public function logout(int $userId): array
    {
        try {
            $user = User::find($userId);
            if ($user) {
                $user->session_token = null;
                $user->save();
            }

            return [
                'status' => true,
                'message' => 'Logged out successfully.'
            ];
        } catch (\Throwable $e) {
            Log::error("AndroidAPI - logout Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Logout failed.',
                'error' => $e->getMessage()
            ];
        }
    }
}
