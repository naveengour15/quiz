<?php

namespace App\Http\Controllers\fsrapp;

use App\Http\Controllers\Controller;
use App\Interfaces\fsrapp\EGiftvoucherRepositoryInterface;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EGiftvoucherController extends Controller
{
    protected $giftVoucherRepo;

    public function __construct(EGiftvoucherRepositoryInterface $giftVoucherRepo)
    {
        $this->giftVoucherRepo = $giftVoucherRepo;
    }

    /**
     * Handle user's request to redeem an e-gift voucher
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestVoucher(Request $request)
    {
        $userId = Auth::id() ?? session('fsr_user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to claim vouchers.'
            ], 401);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User account not found.'
            ], 404);
        }

        $request->validate([
            'voucher_id' => 'required|integer|exists:e_gift_vouchers,id'
        ]);

        $tenantId = $user->tenant_id ?? 1;
        $result = $this->giftVoucherRepo->createVoucherRequest($user->id, (int) $request->voucher_id, $tenantId);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get user's vouchers list via AJAX
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserVouchers(Request $request)
    {
        $userId = Auth::id() ?? session('fsr_user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 401);
        }

        $user = User::find($userId);
        $tenantId = $user->tenant_id ?? 1;

        $requests = $this->giftVoucherRepo->getUserVoucherRequests($userId, $tenantId);

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }
}
