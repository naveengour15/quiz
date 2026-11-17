<?php

namespace App\Repositories\api;

use App\Interfaces\api\DashboardRepositoryInterface;

use App\Models\MobileVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


use App\Models\Survey;
use App\Models\Quiz;

use App\Models\Leaderboard;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use App\Helpers\BuildEmailHelper;


class DashboardRepository implements DashboardRepositoryInterface
{

    public function dashboard($user)
    {
        try {
            if (!$user) {
                return [
                    'status' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated.',
                ];
            }

            $survey = Survey::where('status', 'active')
             ->withExists([
                'rewards as is_joined' => function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                    ->where('status', 'active');
                }
            ])
            ->get();

            $megaQuiz = Quiz::whereIn('status', ['published', 'scheduled'])
                ->where('approve_status', 'approved')
                ->where('title', 'mega')
                ->where(function ($query) use ($user) {
                    $query->where('status', 'public')
                        ->orWhere(function ($query) use ($user) {
                            $query->where('status', 'private')
                                ->where('tenant_id', $user->tenant_id);
                        });
                })
                ->where('scheduled_at', '>', now())
                ->first();
            
            $nextDay = now()->addDay()->startOfDay()->addSecond();
            $nextDayQuizzes = Quiz::whereIn('status', ['published', 'scheduled'])
                ->where('approve_status', 'approved')
                ->where('scheduled_at', '>', $nextDay)
                 ->where('scheduled_at', '>', now())
                ->first();

                
            $startDay = now();
            $endDay = now()->endOfDay();

            $todayQuizzes = Quiz::whereIn('status', ['published', 'scheduled'])
                ->where('approve_status', 'approved')
                ->whereBetween('scheduled_at', [$startDay, $endDay])
                ->with([
                    'userJoin' => function ($q) use ($user) {
                        $q->select([
                            'id',
                            'quiz_id',
                            'user_id',
                            'remark',
                            'status',
                        ])->where('user_id', $user->id);
                    }
                ])
                ->orderBy('scheduled_at', 'asc')
                ->get()
                ->map(function ($quiz) {
                    $quiz->join_status = $quiz->userJoin
                        ? $quiz->userJoin->status
                        : 'pending';

                    return $quiz;
                });

            // Do not hide today quizzes even if quiz limit is 0 so user sees the card with alert
            // if (($user->available_quiz_limit ?? 0) <= 0) {
            //     $todayQuizzes = $todayQuizzes->filter(function ($quiz) {
            //         return $quiz->userJoin !== null;
            //     });
            // }
            $leaderboard = Leaderboard::where('user_id', $user->id)->where('status', 'active')->first();

            $totalWins = \App\Models\QuizWinner::where('user_id', $user->id)->count();
            $recentWinners = \App\Models\QuizWinner::where('user_id', $user->id)->with('quiz')->latest()->take(5)->get();
            $recentActivity = $recentWinners->map(function ($item) {
                return [
                    'quiz_title' => $item->quiz?->title ?? 'Quiz',
                    'title'      => $item->quiz?->title ?? 'Quiz',
                    'status'     => 'Completed',
                    'coins'      => $item->reward ?? '0',
                    'created_at' => $item->created_at ? $item->created_at->diffForHumans() : 'Recently',
                ];
            });

            return [
                'status' => true,
                'code' => 200,
                'message' => 'Dashboard fetched successfully.',
                'data' => [
                    'id'                => $user->id,
                    'name'              => $user->name,
                    'state'             => $user->state,
                    'referral_code'     => $user->referral_code,
                    'available_quiz'    => $user->available_quiz_limit,
                    'create_quiz_limit' => $user->create_quiz_limit,
                    'wallet_balance'    => $user->wallet_balance,
                    'balance'           => $user->wallet_balance,
                    'leaderboard_rank'  => $leaderboard?->rank ?? 1,
                    'leader_board_point' => $leaderboard?->points ?? 0,
                    'rank'              => $leaderboard?->rank ?? 1,
                    'current_rank'      => $leaderboard?->rank ?? 1,
                    'streak'            => 5,
                    'streak_days'       => 5,
                    'total_wins'        => $totalWins,
                    'wins'              => $totalWins,
                    'recent_activity'   => $recentActivity,
                    'recent'            => $recentActivity,
                    'survey'            => $survey,
                    'mega_quiz'         => $megaQuiz,
                    'upcomining_joined_quizzes' => $todayQuizzes,
                    'upcomingQuiz'      => $nextDayQuizzes,
                    'today_quizzes'     => $todayQuizzes,
                    'next_upcoming_quizzes' => $nextDayQuizzes,
                ],
            ];

        } catch (\Throwable $e) {

            Log::error('Dashboard API Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'code' => 500,
                'message' => 'Something went wrong.',
            ];
        }
    }

    public function profile(Request $request)
    {
        try {

            $user = Auth::user();

            if (!$user) {
                return [
                    'status' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated.',
                ];
            }

            $leaderboard = Leaderboard::where('user_id', $user->id)->where('status', 'active')->first();

            return [
                'status' => true,
                'code' => 200,
                'message' => 'Profile fetched successfully.',
                'data' => [
                    'id'                    => $user->id,
                    'tenant_id'             => $user->tenant_id,
                    'name'                  => $user->name,
                    'email'                 => $user->email,
                    'mobile'                => $user->mobile,
                    'alternative_mobile'    => $user->alternative_mobile,
                    'state'                 => $user->state,
                    'city'                  => $user->city,
                    'address'               => $user->address,
                    'gender'                => $user->gender,
                    'date_of_birth'         => $user->date_of_birth,
                    'favorite_color'        => $user->favorite_color,
                    'hobbies'               => $user->hobbies,
                    'games'                 => $user->games,
                    'interest'              => $user->interest,
                    'profile_photo'         => $user->profile_photo,
                    'referral_code'         => $user->referral_code,                    
                    'leaderboard_rank'      => $leaderboard?->rank ?? 0,
                    'leader_board_point'    => $leaderboard?->points ?? 0,
                    'last_login_at'         => $user->last_login_at,
                    'created_at'            => $user->created_at,
                ],
            ];

        } catch (\Throwable $e) {

            Log::error('Profile API Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'code' => 500,
                'message' => 'Something went wrong.',
            ];
        }
    }

    public function completeProfile(Request $request)
    {
        try {

            $request->validate([
                'email' => 'nullable|email|unique:users,email,' . Auth::guard('api')->id(),
                'alternative_mobile' => 'nullable|regex:/^[6-9][0-9]{9}$/',
                'state' => 'required|string|max:100',
                'city' => 'required|string|max:100',
                'address' => 'nullable|string|max:500',
                'gender' => 'required|in:male,female,other',
                'date_of_birth' => 'required|date',
                'favorite_color' => 'nullable|string|max:100',
                'hobbies' => 'nullable|string|max:500',
                'games' => 'nullable|string|max:500',
                'interest' => 'nullable|string|max:500',
                'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $user = Auth::guard('api')->user();

            if (!$user) {
                return [
                    'status' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated.',
                ];
            }

            $photoPath = null;
            $profilePhoto = $request->input('profile_photo');

            if ($profilePhoto && str_starts_with($profilePhoto, 'data:image')) {

                try {

                    Log::info('Processing selfie upload', [
                        'employee_id' => $user->id
                    ]);

                    $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $profilePhoto);
                    $decoded = base64_decode($base64);

                    $filename = 'attendance-selfies/' . uniqid() . '_emp_' . $user->id . '.jpg';

                    Log::info('Uploading selfie to S3', [
                        'path' => $filename
                    ]);

                    Storage::disk('s3')->put(
                        $filename,
                        $decoded,
                        ['visibility' => 'public']
                    );

                    $photoPath = $filename;

                    Log::info('Selfie uploaded successfully', [
                        'path' => $filename
                    ]);

                } catch (\Exception $e) {

                    Log::error('Selfie upload failed during clock-in', [
                        'employee_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            

            $user->update([
                'email' => $request->email,
                'alternative_mobile' => $request->alternative_mobile,
                'state' => $request->state,
                'city' => $request->city,
                'address' => $request->address,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'favorite_color' => $request->favorite_color,
                'hobbies' => $request->hobbies,
                'games' => $request->games,
                'interest' => $request->interest,
                'profile_photo' => $photoPath,
            ]);

            return [
                'status' => true,
                'code' => 200,
                'message' => 'Profile completed successfully.',
                'data' => $user->fresh(),
            ];

        } catch (\Throwable $e) {

            Log::error('Complete Profile API Error', [
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

    public function updateProfile(Request $request)
    {
        try {

            $request->validate([
                'name' => 'nullable|string|max:150',
                'city' => 'nullable|string|max:100',
            ]);

            $user = Auth::guard('api')->user();

            if (!$user) {
                return [
                    'status' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated.',
                ];
            }

            $user->update([
                'name' => $request->name,
                'city' => $request->city,
            ]);

            return [
                'status' => true,
                'code' => 200,
                'message' => 'Profile updated successfully.',
                'data' => $user->fresh(),
            ];

        }catch (\Throwable $e) {

            Log::error('Updated Profile API Error', [
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

    public function updateAltMobile(Request $request)
    {
        try {

            $request->validate([
                'alternative_mobile' => 'nullable|regex:/^[6-9][0-9]{9}$/',
            ]);

            $user = Auth::guard('api')->user();

            if (!$user) {
                return [
                    'status' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated.',
                ];
            }

            $user->update([
                'alternative_mobile' => $request->alternative_mobile,
            ]);

            return [
                'status' => true,
                'code' => 200,
                'message' => 'Alternative mobile updated successfully.',
                'data' => $user->fresh(),
            ];

        }catch (\Throwable $e) {

            Log::error('Update Alternative Mobile API Error', [
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

    public function sendOtpVerifyEmail(Request $request)
    {
        try {

            $user = Auth::guard('api')->user();

            if (!$user) {
                return [
                    'status' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated.',
                ];
            }

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

    public function verifyEmail(Request $request){
        try {
            $request->validate([
                'otp' => 'required|numeric|digits:6',
            ]);
            $user = Auth::guard('api')->user();
          
            if (!$user) {
                return [
                    'status' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated.',
                ];
            }
            
            $mobile = MobileVerification::where('mobile_number', $user->mobile)->where('otp', $request->otp)->first();
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


/********************************** END **********************************/ 
}