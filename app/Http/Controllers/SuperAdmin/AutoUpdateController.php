<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReferralCode;
use App\Models\User;

class AutoUpdateController extends Controller
{
    public function refferalUpdate()
    {
        $tenantId = getTenantId() ?? 1;
        return false;

        // $users = User::where('tenant_id', $tenantId)->get();
        // $refferalUsers = User::where('tenant_id', $tenantId)->whereNotNull('referral_user_id')->get();
                         
        // foreach ($users as $user) {
        //     if ($user->name == null) {
        //         continue;
        //     }

        //     $user->referral_code = $this->generateUniquePlayerName($user->name);
        //     $user->update();

        //     if ($refferalUsers->isNotEmpty()) {
        //         foreach ($refferalUsers as $refferalUser) {
        //             if ($refferalUser->referral_user_id == $user->id) {
        //                 $refferalUser->referral_code_to = $user->referral_code;
        //                 $refferalUser->update();
        //             }
        //         }
        //     }

        //     $refferalCode = ReferralCode::where('user_id', $user->id)->where('code', '!=', $user->referral_code)->get();
        //     foreach ($refferalCode as $referral) {
        //         $referral->code = $user->referral_code;
        //         $referral->update();
        //     }
        // }

        echo 'Referral codes updated successfully! ' . $users->count();
    }
    
    private function generateUniquePlayerName(string $userName): string
    {
        $prefix = strtoupper(str_pad(substr($userName, 0, 3), 3, 'X'));

        do {
            $randomSuffix = str_pad((string) rand(0, 99999), 5, '0', STR_PAD_LEFT);

            $playerName = $prefix . $randomSuffix;

        } while (User::where('referral_code', $playerName)->exists() || User::where('player_name', $playerName)->exists());

        return $playerName;
    }
}