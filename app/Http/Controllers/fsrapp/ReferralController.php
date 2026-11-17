<?php

namespace App\Http\Controllers\fsrapp;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{
    public function index()
    {
        $userId = Auth::id() ?? session('fsr_user_id');
        if (!$userId) {
            return redirect()->route('fsrapp.login');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('fsrapp.login');
        }

        $referralCode = $user->referral_code ?? '—';
        $referrals = User::where('referral_user_id', $user->id)
            ->latest('id')
            ->get();

        return view('fsrapp.referral.index', compact('user', 'referralCode', 'referrals'));
    }
}
