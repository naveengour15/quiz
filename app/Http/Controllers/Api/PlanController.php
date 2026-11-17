<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\api\PlanRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    protected PlanRepositoryInterface $planRepository;

    public function __construct(PlanRepositoryInterface $planRepository)
    {
        $this->planRepository = $planRepository;
    }

    /** GET /api/plans */
    public function list()
    {
        return response()->json($this->planRepository->list());
    }

    /** POST /api/plan/apply-coupon */
    public function applyCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'    => 'required|string|max:191',
            'plan_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json($this->planRepository->applyCoupon($validator->validated()));
    }

    /** POST /api/plan/{plan_id}/pay */
    public function initiatePayment(Request $request, int $planId)
    {
        $validator = Validator::make($request->all(), [
            'payment_type'   => 'required|string|max:50',
            'payment_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json(
            $this->planRepository->initiatePayment($request->user(), $planId, $validator->validated())
        );
    }

    /** POST /api/plan/payment-callback */
    public function paymentCallback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|string',
            'signature'  => 'nullable|string',
            'status'     => 'required|in:success,failed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json($this->planRepository->paymentCallback($validator->validated()));
    }

    /** GET /api/user/subscription */
    public function mySubscription(Request $request)
    {
        return response()->json($this->planRepository->mySubscription($request->user()));
    }

    /** GET /api/user/billing-history */
    public function billingHistory(Request $request)
    {
        return response()->json($this->planRepository->billingHistory($request->user()));
    }

    /** POST /api/user/subscription/cancel */
    public function cancelSubscription(Request $request)
    {
        return response()->json($this->planRepository->cancelSubscription($request->user()));
    }

    /** POST /api/admin/coupon/create */
    public function createCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'                  => 'nullable|string|max:50',
            'discount_type'         => 'required|in:percentage,flat',
            'discount_value'        => 'required|numeric|min:0',
            'usage_limit_total'     => 'nullable|integer',
            'usage_limit_per_user'  => 'nullable|integer',
            'expiry_date'           => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data           = $validator->validated();
        $data['status'] = 'active';

        return response()->json($this->planRepository->createCoupon($data));
    }
}
