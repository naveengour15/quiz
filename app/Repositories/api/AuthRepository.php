<?php

namespace App\Repositories\api;

use App\Interfaces\api\AuthRepositoryInterface;
use App\Models\User;
use App\Models\State;
use App\Models\City;
use App\Models\Tenant;
use App\Models\MobileVerification;
use App\Models\ReferralCode;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Repositories\WhatsAppRepositoryInterface;

use App\Interfaces\ActionTrailRepositoryInterface;

class AuthRepository implements AuthRepositoryInterface
{
    public function __construct(protected ActionTrailRepositoryInterface $actontrail, protected WhatsAppRepositoryInterface $whatsAppRepository)
    {
    }
    
    /**
     * OTP validity window in minutes.
     */
    protected int $otpExpiryMinutes = 5;

    /**
     * {@inheritDoc}
     */

    // public function signup(Request $request)
    // {
    //     DB::beginTransaction();

    //     try {

    //         $request->validate([
    //             'mobile' => 'required|string',
    //             'referral_code' => 'nullable|string',
    //         ]);
            
    //         $state = $request->state_id ? State::find($request->state_id) : null;
    //         $city = null;
    //         if ($request->city_name) {
    //             $city = City::where('name', $request->city_name)->first();
    //             if (!$city && $state) {
    //                 $city = City::create([
    //                     'name' => $request->city_name,
    //                     'state_id' => $state->id,
    //                 ]);                
    //             }
    //         }
            
    //         $tenantId = 1;
    //         $referrelUserId = null;
    //         $walletBalance = 0;
    //         if ($request->referral_code) {
    //             $referrel = ReferralCode::where('code', $request->referral_code)
    //             ->where('used', 'pending')->first();
    //             if ($referrel) {    
    //                 $tenantId = $referrel->tenant_id;
    //                 $referrelUserId = $referrel->user_id;
    //                 if($referrel->referral_point_provide == 'yes'){
    //                     $walletBalance = $referrel->points;
    //                 }
    //             }
    //         }

    //         $user = User::where('mobile', $request->mobile)->first();
    //         if ($user) {

    //             $user->update([
    //                 'name'   => $request->name ?? $user->name,
    //                 'state'  => $state?->name ?? $user->state,
    //                 'city'   => $city?->name ?? $user->city,
    //                 'dob'    => $request->dob ?? $user->dob,
    //                 'gender' => $request->gender ?? $user->gender,
    //                 'referral_code_to' => $request->referral_code ?? $user->referral_code_to,
    //                 'referral_user_id' => $referrelUserId ?? $user->referral_user_id,
    //             ]);
    //             DB::commit();

    //             return [
    //                 'status' => true,
    //                 'code' => 200,
    //                 'message' => 'Profile updated successfully.',
    //                 'data' => $user
    //             ];
    //         }

    //         $nameStr = $request->name ?: 'User';
    //         $initials = strtoupper(substr(preg_replace('/\s+/', '', $nameStr), 0, 3));
    //         $referralCode = $initials . $tenantId . strtoupper(Str::random(6));
    //         $activeValue = 1;

    //         $user = User::create([
    //             'tenant_id' => $tenantId,
    //             'name'    => $request->name ?: 'User',
    //             'mobile'  => $request->mobile,
    //             'password' => bcrypt($request->mobile),
    //             'state'   => $state?->name,
    //             'city'    => $city?->name,
    //             'dob'     => $request->dob,
    //             'gender'  => $request->gender,
    //             'referral_code' => $referralCode,
    //             'referral_code_to' => $request?->referral_code,
    //             'referral_user_id' => $referrelUserId,
    //             'status'  => 'active',
    //             'role' => 'user',
    //             'role_id' => 3,
    //             'create_quiz_limit' => 4,
    //             'available_quiz_limit' => 4,
    //             'quiz_leave' => 0,
    //             'is_active' => $activeValue,
    //             'can_login' => $activeValue,
    //             'otp_enabled' => $activeValue
    //         ]);

    //         DB::commit();

    //         if ($referrel) {    
                
    //             $referrelOldDetails = $referrel->toArray();

    //             $referrel->update(['used' => 'used', 'referral_user_id' => $user->id, 'used_date' => now()]);
                
    //             $this->actontrail->create([
    //                 'tenant_id'    => $referrel->tenant_id,
    //                 'table_name'   => 'referral_codes',
    //                 'reference_id' => $referrel->id,
    //                 'action'       => 'update user',
    //                 'details'      => $referrel->toArray(),
    //                 'oldDetails'   => $referrelOldDetails,
    //             ]);

    //             $referralUser = User::where('id', $referrel->user_id)->first();
    //             $points = (int) $referrel->points;
    //             if ($referralUser) {
    //                 $oldDetails = $referralUser->toArray();

    //                 $referralUser->increment(
    //                     'wallet_balance',
    //                     $points
    //                 );

    //                 $referralUser->refresh();

    //                 Log::info('Referral wallet updated', [
    //                     'user_id' => $referralUser->id,
    //                     'points' => $points,
    //                     'wallet_balance' => $referralUser->wallet_balance,
    //                 ]);

    //                 $this->actontrail->create([
    //                     'tenant_id'    => $referralUser->tenant_id,
    //                     'table_name'   => 'users',
    //                     'reference_id' => $referralUser->id,
    //                     'action'       => 'update wallet',
    //                     'details'      => $referralUser->toArray(),
    //                     'oldDetails'   => $oldDetails,
    //                 ]);
    //             }
               
    //         }
           
    //         $sessionToken = Str::random(60);

    //         $user->session_token = $sessionToken;
    //         $user->last_login_at = now();
    //         $user->save();          

    //         return [
    //             'success' => true,
    //             'message' => 'Registration successful.',
    //             'user' => $user,
    //             'access_token' => $sessionToken,
    //         ];

    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         Log::error('Signup API Error', [
    //             'message' => $e->getMessage(),
    //             'line' => $e->getLine(),
    //             'file' => $e->getFile(),
    //         ]); 

    //         return [
    //             'status' => false,
    //             'code' => 500,
    //             'message' => 'Something went wrong.'
    //         ];
    //     }
    // }

    public function signup(Request $request)
    {
        DB::beginTransaction();

        try {

            /*
            * Validate request
            */
            $request->validate([
                'mobile' => 'required|string',
                'name' => 'nullable|string',
                'referral_code' => 'nullable|string',
            ]);

            /*
            * State
            */
            $state = $request->state_id
                ? State::find($request->state_id)
                : null;

            /*
            * City
            */
            $city = null;

            if ($request->city_name) {

                $city = City::where('name', $request->city_name)->first();

                if (!$city && $state) {
                    $city = City::create([
                        'name' => $request->city_name,
                        'state_id' => $state->id,
                    ]);
                }
            }

            /*
            * Default tenant
            */
            $tenantId = 1;

            $referralUserId = null;

            /*
            * Find referral code
            */
            $referral = null;

            if (!empty($request->referral_code)) {

                $referral = ReferralCode::where(
                    'code',
                    $request->referral_code
                )
                    ->where('used', 'pending')
                    ->where('status', 'active')
                    ->first();

                if (!$referral) {

                    return [
                        'status' => false,
                        'code' => 422,
                        'message' => 'Invalid or already used referral code.',
                    ];
                }

                $tenantId = $referral->tenant_id;

                $referralUserId = $referral->user_id;
            }

            /*
            * Check existing user
            */
            $user = User::where('mobile', $request->mobile)
                ->whereNotNull('referral_code_to')
                ->first();

            if ($user) {

                $oldDetails = $user->toArray();

                $user->update([
                    'name' => $request->name ?? $user->name,
                    'state' => $state?->name ?? $user->state,
                    'city' => $city?->name ?? $user->city,
                    'dob' => $request->dob ?? $user->dob,
                    'gender' => $request->gender ?? $user->gender,
                    'referral_code_to' =>
                        $request->referral_code ?? $user->referral_code_to,
                    'referral_user_id' =>
                        $referralUserId ?? $user->referral_user_id,
                ]);


                $sessionToken = Str::random(60);

                $user->update([
                    'session_token' => $sessionToken,
                    'last_login_at' => now(),
                ]);

                DB::commit();

                return [
                    'status' => true,
                    'code' => 201,
                    'message' => 'Login Successfull.',
                    'user' => $user->fresh(),
                    'access_token' => $sessionToken,
                ];

                
            }

            /*
            * Generate user's own referral code
            */
            $nameStr = $request->name ?: 'User';

            $initials = strtoupper(
                substr(
                    preg_replace('/\s+/', '', $nameStr),
                    0,
                    3
                )
            );

            $referralCode = $initials
                . $tenantId
                . strtoupper(Str::random(6));

            /*
            * Create user
            */
            $user = User::create([
                'tenant_id' => $tenantId,

                'name' => $nameStr,
                'mobile' => $request->mobile,

                'password' => bcrypt($request->mobile),

                'state' => $state?->name,
                'city' => $city?->name,

                'dob' => $request->dob,
                'gender' => $request->gender,

                'referral_code' => $referralCode,
                'referral_code_to' => $request->referral_code,
                'referral_user_id' => $referralUserId,

                'status' => 'active',

                'role' => 'user',
                'role_id' => 3,

                'create_quiz_limit' => 4,
                'available_quiz_limit' => 4,
                'quiz_leave' => 0,

                'wallet_balance' => 0,

                'is_active' => 1,
                'can_login' => 1,
                'otp_enabled' => 1,
            ]);

            /*
            * Process referral
            */
            if ($referral) {

                $referralOldDetails = $referral->toArray();

                $points = (int) ($referral->points ?? 0);

                /*
                * Mark referral code as used
                */
                $referral->update([
                    'used' => 'used',
                    'referral_user_id' => $user->id,
                    'used_date' => now(),
                ]);

                /*
                * Action trail
                */
                $this->actontrail->create([
                    'tenant_id' => $referral->tenant_id,
                    'table_name' => 'referral_codes',
                    'reference_id' => $referral->id,
                    'action' => 'update user',
                    'details' => $referral->toArray(),
                    'oldDetails' => $referralOldDetails,
                ]);

                /*
                * Add points to referral owner
                */
                if (
                    $referral->referral_point_provide === 'yes'
                    && $points > 0
                    && $referral->user_id
                ) {

                    $referralUser = User::find($referral->user_id);

                    if ($referralUser) {

                        $oldDetails = $referralUser->toArray();

                        $referralUser->increment(
                            'wallet_balance',
                            $points
                        );

                        $referralUser->refresh();

                        Log::info('Referral wallet updated', [
                            'user_id' => $referralUser->id,
                            'points' => $points,
                            'wallet_balance' => $referralUser->wallet_balance,
                        ]);

                        $this->actontrail->create([
                            'tenant_id' => $referralUser->tenant_id,
                            'table_name' => 'users',
                            'reference_id' => $referralUser->id,
                            'action' => 'update wallet',
                            'details' => $referralUser->toArray(),
                            'oldDetails' => $oldDetails,
                        ]);
                    }
                }
            }

            /*
            * Session token
            */
            $sessionToken = Str::random(60);

            $user->update([
                'session_token' => $sessionToken,
                'last_login_at' => now(),
            ]);

            DB::commit();

            return [
                'status' => true,
                'code' => 201,
                'message' => 'Registration successful.',
                'user' => $user->fresh(),
                'access_token' => $sessionToken,
            ];

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Signup API Error', [
                'mobile' => $request->mobile,
                'referral_code' => $request->referral_code,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'code' => 500,
                'message' => 'Something went wrong.',
            ];
        }
    }

    public function sendOtp(array $data): array
    {
        try {

            $mobile   = $data['mobile'];
            $tenantId = $data['tenant_id'] ?? 1;

            $otp   = (string) random_int(1000, 9999);
            $token = Str::random(40);

            MobileVerification::updateOrCreate(
                ['mobile_number' => $mobile],
                [
                    'tenant_id' => $tenantId,
                    'otp' => $otp,
                    'token' => $token,
                ]
            );

            $status = $this->sendWhatsAppSms($mobile, $otp, $tenantId);

            if ($status === 'sent') {

                // Send SMS (optional fallback or additional channel)
                $this->dispatchSms($mobile, $otp);

                $isNewUser = !User::where('mobile', $mobile)->exists();

                Log::info('OTP sent successfully.', [
                    'mobile' => $mobile,
                    'tenant_id' => $tenantId,
                ]);

                return [
                    'success' => true,
                    'message' => 'OTP sent successfully.',
                    'token' => $token,
                    'is_new_user' => $isNewUser,
                    'otp' => $otp,
                ];
            }

            Log::warning('WhatsApp OTP sending failed.', [
                'mobile' => $mobile,
                'tenant_id' => $tenantId,
            ]); 

            return [
                'success' => false,
                'message' => 'OTP WhatsApp sending failed.',
            ];

        } catch (\Throwable $e) {

            Log::error('Send OTP Repository Error', [
                'mobile' => $data['mobile'] ?? null,
                'tenant_id' => $data['tenant_id'] ?? null,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Something went wrong while sending OTP.',
            ];
        }
    }


    /**
     * {@inheritDoc}
     */

    public function verifyOtp(array $data): array
    {
        try {

            $mobile = $data['mobile'];
            $otp    = $data['otp'];
            $token  = $data['token'];

            $verification = MobileVerification::where('mobile_number', $mobile)
                ->where('token', $token)
                ->latest('id')
                ->first();

            if (!$verification) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired token.',
                ];
            }

            if ($verification->otp != $otp) {
                return [
                    'success' => false,
                    'message' => 'Invalid OTP.',
                ];
            }

            if (Carbon::parse($verification->updated_at)
                ->addMinutes($this->otpExpiryMinutes)
                ->isPast()) {

                return [
                    'success' => false,
                    'message' => 'OTP has expired. Please resend OTP.',
                ];
            }

            $user = User::where('mobile', $mobile)->first();

            if (!$user) {

                $user = User::create([
                    'tenant_id'     => $data['tenant_id'] ?? 1,
                    'name'          => $data['name'] ?? null,
                    'mobile'        => $mobile,
                    'login_method'  => 'otp',
                    'password'      => bcrypt(Str::random(16)),
                    'role'          => 'user',
                    'verify_mobile' => 1,
                    'referral_code' => strtoupper(Str::random(8)),
                    'is_active'     => 1,
                    'can_login'     => 1,
                    'otp_enabled'   => 1,
                ]);

                Log::info('New user created after OTP verification.', [
                    'user_id' => $user->id,
                    'mobile' => $mobile,
                ]);

            } else {

                $user->verify_mobile = 1;
            }
            
            $referralCode = ReferralCode::where('referral_user_id', $user->id)->first();
            
            
            if(!$referralCode)
            {
                 
                return [
                    'success' => true,
                    'message' => 'OTP verified successfully.',
                    'is_new_user' => true,
                    'referral_code_verify' => false
                ];
            }

            $sessionToken = Str::random(60);

            $user->session_token = $sessionToken;
            $user->last_login_at = now();
            $user->save();

            // OTP consumed
            $verification->delete();

            Log::info('User OTP verified successfully.', [
                'user_id' => $user->id,
                'mobile' => $mobile,
            ]);

            return [
                'success' => true,
                'message' => 'OTP verified successfully.',
                'user' => $user,
                'is_new_user' => false,
                'referral_code_verify' => true,
                'access_token' => $sessionToken,
            ];

        } catch (\Throwable $e) {

            Log::error('Verify OTP Repository Error', [
                'mobile' => $data['mobile'] ?? null,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Something went wrong while verifying OTP.',
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function resendOtp(array $data): array
    {
        // Same flow as sendOtp — generates & stores a fresh OTP/token
        return $this->sendOtp($data);
    }

    /**
     * {@inheritDoc}
     */
    public function logout($user): array
    {
        $user->session_token = null;
        $user->save();

        return [
            'success' => true,
            'message' => 'Logged out successfully',
        ];
    }

    /**
     * Send OTP via SMS gateway. Replace with real provider integration.
     */
    protected function dispatchSms(string $mobile, string $otp): void
    {
        // TODO: integrate MSG91 / Twilio / Fast2SMS etc.
        Log::info("OTP for {$mobile}: {$otp}");
    }

    
    public function sendWhatsAppSms($mobile, $otp, $tenantId)
    {
        if (!str_starts_with($mobile, '91')) {
            $mobile = '91' . $mobile;
        }

        $parameters = [
            [
                'name' => 'otp',
                'value' => $otp,
            ],
            [
                'name' => 'company_name',
                'value' => 'FsrQuiz',
            ],
        ];

        $buttonParameters = [
            [
                'index' => 0,
                'value' => 'verify',
            ],
        ];

        $tenant = Tenant::find($tenantId);
        
        $response = $this->whatsAppRepository->sendWhatsAppMessage(
            $mobile,
            'CHALO_OTP',
            $parameters,
            $buttonParameters,
            $tenant
        );

        $responseData = json_decode($response);

        $status = 'failed';

        if (isset($responseData->messages[0]->message_status)) {
            $status = $responseData->messages[0]->message_status;
             $status = $responseData->messages[0]->message_status == 'accepted' ? 'sent' : $responseData->messages[0]->message_status;
        }
        return $status;
    }


    /******************************* END *******************************/ 
}
