<?php

namespace App\Repositories\api;

use App\Interfaces\api\AuthUserRepositoryInterface;
use App\Models\User;
use App\Models\State;
use App\Models\City;
use App\Models\Tenant;
use App\Models\MobileVerification;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\WhatsAppRepositoryInterface;

use Illuminate\Support\Facades\Auth;

class AuthUserRepository implements AuthUserRepositoryInterface
{

    public function __construct(protected WhatsAppRepositoryInterface $whatsAppRepository)
    {
    }
    public function signup(Request $request)
    {
        DB::beginTransaction();

        try {

            if (User::where('mobile', $request->mobile)->exists()) {

                return [
                    'status' => false,
                    'code' => 422,
                    'message' => 'Mobile number already registered.'
                ];
            }

            $state = $request->state_id;

            $state = State::where('id', $request->state_id)->firstOrFail();

            $city = City::where('name', $request->city_name)->firstOrFail();
            if (!$city) {
                $city = City::create([
                    'name' => $request->city_name,
                    'state_id' => $state->id,
                ]);                
            }
            
            $tenantId = 1;
            $referrerUserId = null;
            if($request->referral_code != '') {
                $referrer = User::where('referral_code', $request->referral_code)->first();

                if ($referrer) {    
                    $tenantId = $referrer->tenant_id;
                    $referrerUserId = $referrer->id;
                }
            }

            $initials = strtoupper(substr(preg_replace('/\s+/', '', $request->name), 0, 3));

            $referralCode = $initials . $tenantId . strtoupper(Str::random(6));
            $activeValue = 1;
            $user = User::create([
                'tenant_id' => $tenantId,
                'name'    => $request->name,
                'mobile'  => $request->mobile,
                'password' => bcrypt($request->mobile),
                'state'   => $state?->name,
                'city'    => $city?->name,
                'dob'     => $request->dob,
                'gender'  => $request->gender,
                'referral_code' => $referralCode,
                'referral_code_to' => $request?->referral_code,
                'referral_user_id' => $referrerUserId,
                'status'  => 'active',
                'role' => 'user',
                'role_id' => 3,
                'create_quiz_limit' => 4,
                'available_quiz_limit' => 4,
                'quiz_leave' => 0,
                'is_active' => $activeValue,
                'can_login' => $activeValue,
                'otp_enabled' => $activeValue
            ]);

            DB::commit();

            return [
                'status' => true,
                'code' => 201,
                'message' => 'Registration successful.',
                'data' => $user
            ];

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Send Otp API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]); 

            return [
                'status' => false,
                'code' => 500,
                'message' => 'Something went wrong.'
            ];
        }
    }

    public function sendOtp(Request $request)
    {
        try {

            $request->validate([
                'mobile' => [
                    'required',
                    'regex:/^[6-9][0-9]{9}$/',
                ],
            ]);

            $otp = random_int(100000, 999999);

            $mobile = $request->mobile;

            if (!str_starts_with($mobile, '91')) {
                $mobile = '91' . $mobile;
            }

            MobileVerification::updateOrCreate(
                [
                    'mobile_number' => $request->mobile,
                ],
                [
                    'tenant_id' => 1,
                    'otp' => $otp,
                    'token' => (string) Str::uuid(),
                ]
            );

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

            $tenant = Tenant::find(1);
           
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
            }
 
            Log::info('OTP Generated Successfully', [
                'mobile' => $mobile,
                'status' => $status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully.',
                // 'otp' => $otp, // Enable only for local/testing
            ]);

        } catch (\Throwable $e) {

            Log::error('Send OTP API Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|numeric|digits:10',
            'otp' => 'required|numeric|digits:6',
        ]);
    

        $mobile = MobileVerification::where('mobile_number', $request->mobile)->where('otp', $request->otp)->first();
     
        if ($mobile) {
            $user = User::where('mobile', $request->mobile)->first();
            $adminStaff = null;
          
            $tenantId = null;
            
            $mobile->delete();

            if ($user) {
                $tenantId = $user->id;              
            } elseif (!$user) {
                return response()->json([
                    'status' => true,
                    'message' => "Otp verification successful, User account not found.",
                    'data' => [
                        "mobile" => $request->mobile,
                        "redirect_url" => "signup",
                    ],
                ], 200);
            }
            
            // Using Passport
            $tokenResult = $user->createToken('myApp');
            $token = $tokenResult->accessToken;
            
        
            $res = [
                'status' => true,
                'message' => "Otp verification successful",
                'data' => [
                    'token' => $token,
                    'user' => $user,
                    'tenant_id' => $user->tenant_id,
                    'is_user' => $adminStaff ? true : false,
                    'user_id' => $user ? $user->id : null
                ],
            ];
        } else {
            $res = [
                'status' => false,
                'message' => "Invalid Otp",
                'data' => [],
            ];
        }        
        return response()->json($res, 200);
    }

    /*******************************************/ 
}