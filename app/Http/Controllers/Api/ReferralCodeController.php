<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\api\ReferralCodeRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

use App\Models\User;

class ReferralCodeController extends Controller
{
    public function __construct(
        protected ReferralCodeRepository $referralCodeRepository
    ) {
    }


     public function generate(Request $request): JsonResponse
    {
        try {

            $validated = $request->validate([
                'mobile' => 'required|string|exists:users,mobile',
                'point_provide' => 'nullable|string'
            ]);

            $referralCode = $this->referralCodeRepository->generate(
                $validated['mobile'], $validated['point_provide']
            );

            if (!$referralCode) {
                return response()->json([
                    'status' => false,
                    'message' => 'Retailer not found',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Referral code generated successfully.',
                'data' => [
                    'id' => $referralCode->id,
                    'tenant_id' => $referralCode->tenant_id,
                    'user_id' => $referralCode->user_id,
                    'code' => $referralCode->code,
                    'used' => $referralCode->used,
                    'status' => $referralCode->status,
                ],
            ], 201);

        } catch (\Throwable $e) {

            Log::error('Referral code generation failed', [
                'mobile' => $request->mobile,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while generating referral code.',
            ], 500);
        }
    }


    private function generateUniqueCode($name): string
    {
        do {
            $code = strtoupper(Str::random(5));
        } while ($this->referralCodeRepository->codeExists($code));

        return strtoupper(substr($name, 0, 4)).$code;
    }
}