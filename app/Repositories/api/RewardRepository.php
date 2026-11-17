<?php

namespace App\Repositories\api;

use App\Interfaces\api\RewardRepositoryInterface;
use App\Models\Redemption;
use App\Models\Reward;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\DB;

class RewardRepository implements RewardRepositoryInterface
{
    public function catalog(): array
    {
        $rewards = Reward::where('status', 'active')->get();

        return ['success' => true, 'data' => $rewards];
    }

    public function redeem($user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            $reward = Reward::find($data['reward_id']);

            if (! $reward || $reward->status !== 'active') {
                return ['success' => false, 'message' => 'Reward not available'];
            }

            if ($reward->stock !== null && $reward->stock <= 0) {
                return ['success' => false, 'message' => 'Reward is out of stock'];
            }

            $coins = (int) $data['coins'];

            if ($user->wallet_balance < $coins) {
                return ['success' => false, 'message' => 'Insufficient wallet balance'];
            }

            $redemption = Redemption::create([
                'tenant_id' => $user->tenant_id ?? 1,
                'user_id'   => $user->id,
                'reward_id' => $reward->id,
                'coins'     => $coins,
                'status'    => 'pending',
            ]);

            $user->decrement('wallet_balance', $coins);

            WalletHistory::create([
                'tenant_id'        => $user->tenant_id ?? 1,
                'user_id'          => $user->id,
                'amount'           => $coins,
                'type'             => 'debit',
                'transaction_type' => 'reward_redemption',
                'reference_id'     => (string) $redemption->id,
                'remarks'          => "Redeemed for {$reward->name}",
            ]);

            if ($reward->stock !== null) {
                $reward->decrement('stock');
            }

            return ['success' => true, 'message' => 'Redemption request submitted', 'redemption' => $redemption];
        });
    }

    public function myRedemptions($user): array
    {
        $data = Redemption::where('user_id', $user->id)->latest('id')->paginate(20);

        return ['success' => true, 'data' => $data];
    }

    public function walletBalance($user): array
    {
        return ['success' => true, 'wallet_balance' => $user->wallet_balance];
    }

    public function walletHistory($user): array
    {
        $data = WalletHistory::where('user_id', $user->id)->latest('id')->paginate(20);

        return ['success' => true, 'data' => $data];
    }

    public function actionOnRedemption(int $redemptionId, array $data): array
    {
        $redemption = Redemption::find($redemptionId);

        if (! $redemption) {
            return ['success' => false, 'message' => 'Redemption not found'];
        }

        $redemption->status     = $data['status'];
        $redemption->fraud_score = $data['fraud_score'] ?? $redemption->fraud_score;
        $redemption->save();

        return ['success' => true, 'message' => 'Redemption updated', 'redemption' => $redemption];
    }
}
