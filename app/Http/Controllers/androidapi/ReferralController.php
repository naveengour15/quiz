<?php

namespace App\Http\Controllers\androidapi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\androidapi\Traits\ResolvesAndroidUser;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{
    use ResolvesAndroidUser;

    public function getReferralData(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $referralCode = $user->referral_code ?? '—';
        $referrals = User::where('referral_user_id', $user->id)
            ->select('id', 'name', 'player_name', 'profile_photo', 'created_at')
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'player_name' => $user->player_name,
                'mobile' => $user->mobile,
                'referral_code' => $referralCode,
                'wallet_balance' => (int)($user->wallet_balance ?? 0),
            ],
            'referral_code' => $referralCode,
            'wallet_balance' => (int)($user->wallet_balance ?? 0),
            'referrals' => $referrals,
            'share_text' => "Play exciting daily quizzes, win reward points, and top the leaderboard on FSRQuiz! Use my referral code: *{$referralCode}* to join: " . url('/')
        ]);
    }

    public function code(Request $request)
    {
        return $this->getReferralData($request);
    }

    public function referrals(Request $request)
    {
        return $this->getReferralData($request);
    }

    public function apply(Request $request)
    {
        $request->validate(['referral_code' => 'required|string|max:191']);
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return DB::transaction(function () use ($request, $user) {
            $referrer = User::where('referral_code', $request->input('referral_code'))->first();
            if (!$referrer) {
                return response()->json(['success' => false, 'message' => 'Invalid referral code'], 422);
            }
            if ($referrer->id === $user->id) {
                return response()->json(['success' => false, 'message' => 'You cannot use your own referral code'], 422);
            }
            if (Referral::where('referred_user_id', $user->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Referral code already applied for your account'], 422);
            }

            $referral = Referral::create([
                'tenant_id' => $referrer->tenant_id ?? 1,
                'user_id' => $referrer->id,
                'referred_user_id' => $user->id,
                'referral_code' => $request->input('referral_code'),
                'status' => 'pending',
                'reward_amount' => 10,
                'referred_reward_amount' => 5,
            ]);
            $user->update([
                'referral_code_to' => $request->input('referral_code'),
                'referral_user_id' => $referrer->id,
            ]);

            return response()->json(['success' => true, 'message' => 'Referral code applied successfully', 'data' => $referral]);
        });
    }
}
