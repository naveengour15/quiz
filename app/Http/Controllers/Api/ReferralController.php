<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\api\ReferralRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReferralController extends Controller
{
    protected ReferralRepositoryInterface $referralRepository;

    public function __construct(ReferralRepositoryInterface $referralRepository)
    {
        $this->referralRepository = $referralRepository;
    }

    /** GET /api/user/referral-code */
    public function myCode(Request $request)
    {
        return response()->json($this->referralRepository->myCode($request->user()));
    }

    /** POST /api/user/apply-referral */
    public function applyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referral_code' => 'required|string|max:191',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json(
            $this->referralRepository->applyCode($request->user(), $request->input('referral_code'))
        );
    }

    /** GET /api/user/referrals */
    public function myReferrals(Request $request)
    {
        return response()->json($this->referralRepository->myReferrals($request->user()));
    }

    /** POST /api/admin/referral/{id}/settle */
    public function settle(Request $request, int $id)
    {
        return response()->json($this->referralRepository->settle($id, $request->all()));
    }
}
