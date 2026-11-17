<?php

namespace App\Repositories\api;

use App\Interfaces\api\PlanRepositoryInterface;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\PlanBilling;
use App\Models\PlanPayment;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanRepository implements PlanRepositoryInterface
{
    public function list(): array
    {
        return ['success' => true, 'data' => Plan::where('status', 'active')->get()];
    }

    public function applyCoupon(array $data): array
    {
        $coupon = Coupon::where('code', $data['code'])->where('status', 'active')->first();

        if (! $coupon) {
            return ['success' => false, 'message' => 'Invalid coupon code'];
        }

        if ($coupon->expiry_date && now()->gt($coupon->expiry_date)) {
            return ['success' => false, 'message' => 'Coupon has expired'];
        }

        if ($coupon->usage_limit_total !== null && $coupon->used_count >= $coupon->usage_limit_total) {
            return ['success' => false, 'message' => 'Coupon usage limit reached'];
        }

        $plan = Plan::find($data['plan_id']);

        if (! $plan) {
            return ['success' => false, 'message' => 'Plan not found'];
        }

        $discount = $coupon->discount_type === 'percentage'
            ? ($plan->amount * $coupon->discount_value / 100)
            : $coupon->discount_value;

        $finalAmount = max(0, $plan->amount - $discount);

        return [
            'success'      => true,
            'coupon'       => $coupon,
            'plan_amount'  => $plan->amount,
            'discount'     => $discount,
            'final_amount' => $finalAmount,
        ];
    }

    public function initiatePayment($user, int $planId, array $data): array
    {
        $plan = Plan::find($planId);

        if (! $plan) {
            return ['success' => false, 'message' => 'Plan not found'];
        }

        $payment = PlanPayment::create([
            'tenant_id'      => $user->tenant_id ?? 1,
            'contact'        => $user->mobile,
            'user_id'        => $user->id,
            'plan_id'        => $plan->id,
            'payment_type'   => $data['payment_type'],
            'payment_amount' => $data['payment_amount'],
            'status'         => 'pending',
        ]);

        // NOTE: integrate real payment gateway (Razorpay/Stripe/etc.) here and
        // return their order/session id to the client for checkout.
        return [
            'success'    => true,
            'message'    => 'Payment initiated',
            'payment_id' => $payment->id,
            'plan_id'    => $plan->id,
            'amount'     => $data['payment_amount'],
        ];
    }

    public function paymentCallback(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $payment = PlanPayment::where('id', $data['payment_id'])
                ->orWhere('payment_id', $data['payment_id'])
                ->first();

            if (! $payment) {
                return ['success' => false, 'message' => 'Payment record not found'];
            }

            $payment->payment_id = $data['payment_id'];
            $payment->signature   = $data['signature'] ?? null;
            $payment->status      = $data['status'];
            $payment->save();

            if ($data['status'] !== 'success') {
                return ['success' => false, 'message' => 'Payment failed or cancelled'];
            }

            $plan = Plan::find($payment->plan_id);

            $billing = PlanBilling::create([
                'tenant_id'      => $payment->tenant_id,
                'user_id'        => $payment->user_id,
                'payment_id'     => $payment->id,
                'plan_id'        => $plan->id ?? null,
                'plan_name'      => $plan->name ?? null,
                'validity_days'  => $plan->days ?? null,
                'plan_amount'    => $payment->payment_amount,
                'payable_amount' => $payment->payment_amount,
                'plan_start_date'=> now()->toDateString(),
                'plan_end_date'  => now()->addDays($plan->days ?? 30)->toDateString(),
                'payment_method' => $payment->payment_type,
                'transaction_id' => $payment->payment_id,
                'billing_date'   => now(),
                'expiry_date'    => now()->addDays($plan->days ?? 30),
                'payment_status' => 'paid',
                'status'         => 'active',
            ]);

            UserSubscription::updateOrCreate(
                ['user_id' => $payment->user_id],
                [
                    'tenant_id'      => $payment->tenant_id,
                    'plan_id'        => $plan->id ?? null,
                    'start_date'     => now()->toDateString(),
                    'renewal_date'   => now()->addDays($plan->days ?? 30)->toDateString(),
                    'amount'         => $payment->payment_amount,
                    'payment_method' => $payment->payment_type,
                    'status'         => 'active',
                ]
            );

            return ['success' => true, 'message' => 'Payment verified, subscription activated', 'billing' => $billing];
        });
    }

    public function mySubscription($user): array
    {
        $subscription = UserSubscription::where('user_id', $user->id)->where('status', 'active')->first();

        return ['success' => true, 'subscription' => $subscription];
    }

    public function billingHistory($user): array
    {
        $data = PlanBilling::where('user_id', $user->id)->latest('id')->paginate(20);

        return ['success' => true, 'data' => $data];
    }

    public function cancelSubscription($user): array
    {
        UserSubscription::where('user_id', $user->id)->update(['status' => 'cancelled']);

        return ['success' => true, 'message' => 'Subscription cancelled successfully'];
    }

    public function createCoupon(array $data): array
    {
        $data['code'] = $data['code'] ?? strtoupper(Str::random(8));

        $coupon = Coupon::create($data);

        return ['success' => true, 'message' => 'Coupon created successfully', 'coupon' => $coupon];
    }
}
