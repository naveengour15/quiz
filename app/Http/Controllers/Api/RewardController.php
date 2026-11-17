<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\api\RewardRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RewardController extends Controller
{
    protected RewardRepositoryInterface $rewardRepository;

    public function __construct(RewardRepositoryInterface $rewardRepository)
    {
        $this->rewardRepository = $rewardRepository;
    }

    /** GET /api/user/rewards */
    public function catalog()
    {
        return response()->json($this->rewardRepository->catalog());
    }

    /** POST /api/user/redeem */
    public function redeem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reward_id' => 'required|integer',
            'coins'     => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json(
            $this->rewardRepository->redeem($request->user(), $validator->validated())
        );
    }

    /** GET /api/user/redemptions */
    public function myRedemptions(Request $request)
    {
        return response()->json($this->rewardRepository->myRedemptions($request->user()));
    }

    /** GET /api/user/wallet */
    public function walletBalance(Request $request)
    {
        return response()->json($this->rewardRepository->walletBalance($request->user()));
    }

    /** GET /api/user/wallet-history */
    public function walletHistory(Request $request)
    {
        return response()->json($this->rewardRepository->walletHistory($request->user()));
    }

    /** POST /api/admin/redemption/{id}/action */
    public function actionOnRedemption(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'status'      => 'required|in:pending,approved,rejected,fulfilled',
            'fraud_score' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json(
            $this->rewardRepository->actionOnRedemption($id, $validator->validated())
        );
    }
}
