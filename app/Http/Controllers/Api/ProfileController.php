<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Interfaces\api\ProfileRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{

    public function __construct(
        protected ProfileRepositoryInterface $profileRepository
    ) {}

    /** GET /api/user/profile */
    public function getProfile(Request $request)
    {
        return response()->json($this->profileRepository->getProfile($request->user()));
    }

    /** POST /api/user/complete-profile & /api/user/update-profile */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'nullable|string|max:191',
            'email'          => 'nullable|email|max:191',
            'gender'         => 'nullable|in:male,female,other',
            'date_of_birth'  => 'nullable|date',
            'state'          => 'nullable|string|max:191',
            'city'           => 'nullable|string|max:191',
            'address'        => 'nullable|string',
            'lat'            => 'nullable|numeric',
            'long'           => 'nullable|numeric',
            'favorite_color' => 'nullable|string|max:191',
            'hobbies'        => 'nullable|string',
            'games'          => 'nullable|string',
            'interest'       => 'nullable|string',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->profileRepository->updateProfile($request->user(), $validator->validated(), 
        $request->file('profile_photo'));

        return response()->json($result);
    }

    /** POST /api/user/update-alt-mobile */
    public function updateAlternativeMobile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alternative_mobile' => 'required|digits_between:10,15',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->profileRepository->updateAlternativeMobile(
            $request->user(),
            $request->input('alternative_mobile')
        );

        return response()->json($result);
    }


    
    public function sendOtpVerifyEmail(Request $request)
    {
        $result = $this->profileRepository->sendOtpVerifyEmail($request->user());

        return response()->json($result);
    }

    /** POST /api/user/verify-email */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:191',
            'otp'   => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->profileRepository->verifyEmailOtp($request->user(), $validator->validated());

        return response()->json($result);
    }

    /** DELETE /api/user/delete-account */
    public function deleteAccount(Request $request)
    {
        return response()->json($this->profileRepository->deleteAccount($request->user()));
    }
}
