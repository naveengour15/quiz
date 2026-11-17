<?php

namespace App\Repositories\api;

use App\Interfaces\api\ReferralRepositoryInterface;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReferralRepository implements ReferralRepositoryInterface
{
    /**
     * Default reward amounts — move to settings/config table if these need
     * to be admin-configurable.
     */
    protected float $referrerReward = 10.00;
    protected float $referredReward = 5.00;

    public function myCode($user): array
    {
        return ['success' => true, 'referral_code' => $user->referral_code];
    }

    public function applyCode($user, string $code): array
    {
        return DB::transaction(function () use ($user, $code) {
            $referrer = User::where('referral_code', $code)->first();

            if (! $referrer) {
                return ['success' => false, 'message' => 'Invalid referral code'];
            }

            if ($referrer->id === $user->id) {
                return ['success' => false, 'message' => 'You cannot use your own referral code'];
            }

            $alreadyReferred = Referral::where('referred_user_id', $user->id)->exists();

            if ($alreadyReferred) {
                return ['success' => false, 'message' => 'Referral code already applied for your account'];
            }

            $referral = Referral::create([
                'tenant_id'                => $referrer->tenant_id ?? 1,
                'user_id'                  => $referrer->id,
                'referred_user_id'         => $user->id,
                'referral_code'            => $code,
                'status'                   => 'pending',
                'reward_amount'            => $this->referrerReward,
                'reward_given'             => 0,
                'referred_reward_amount'   => $this->referredReward,
                'referred_reward_given'    => 0,
            ]);

            $user->referral_code_to  = $code;
            $user->referral_user_id  = $referrer->id;
            $user->save();

            return ['success' => true, 'message' => 'Referral code applied successfully', 'referral' => $referral];
        });
    }

    public function myReferrals($user): array
    {
        $data = Referral::where('user_id', $user->id)->latest('id')->paginate(20);

        return ['success' => true, 'data' => $data];
    }

    public function settle(int $referralId, array $data): array
    {
        return DB::transaction(function () use ($referralId, $data) {
            $referral = Referral::find($referralId);

            if (! $referral) {
                return ['success' => false, 'message' => 'Referral not found'];
            }

            $referral->status       = 'completed';
            $referral->reward_given = 1;
            $referral->reward_at    = now();
            $referral->save();

            $referrer = User::find($referral->user_id);
            if ($referrer) {
                $referrer->increment('wallet_balance', $referral->reward_amount);
            }

            $referred = User::find($referral->referred_user_id);
            if ($referred && ! $referral->referred_reward_given) {
                $referred->increment('wallet_balance', $referral->referred_reward_amount);
                $referral->referred_reward_given = 1;
                $referral->save();
            }

            return ['success' => true, 'message' => 'Referral reward settled', 'referral' => $referral];
        });
    }
}
