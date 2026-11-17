<?php

namespace App\Repositories\api;


use App\Models\ReferralCode;
use App\Models\User;

use App\Interfaces\ActionTrailRepositoryInterface;
class ReferralCodeRepository
{

    public function __construct(protected ActionTrailRepositoryInterface $actontrail) {
       
    }

    public function generate(int $mobile, string $pointProvide='no'): ReferralCode
    {
        $user = User::where('mobile', $mobile)->first();

        $code = $this->generateUniqueCode($user->name);
        $points= 0;
        if($pointProvide == 'yes'){
            $points= 10;
        }
        $referralCode = ReferralCode::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'code' => $code,
            'referral_point_provide' => $pointProvide,
            'points' => $points,
            'referral_user_id' => null,
            'used' => 'pending',
            'used_date' => null,
            'status' => 'active',
        ]);


        $this->actontrail->create([
            'tenant_id'    => $referralCode->tenant_id,
            'table_name'   => 'referral_codes',
            'reference_id' => $referralCode->id,
            'action'       => 'add',
            'details'      => $referralCode->toArray(),
        ]);

        return $referralCode;
    }

     private function generateUniqueCode(string $name): string
    {
        $name = preg_replace('/[^A-Za-z]/', '', $name);

        $prefix = strtoupper(substr($name, 0, 4));

        $prefix = str_pad($prefix, 4, 'X');

        do {
            $code = $prefix . random_int(10000, 99999);
        } while (
            ReferralCode::where('code', $code)->exists()
        );

        return $code;
    }

    public function codeExists(string $code): bool
    {
        return ReferralCode::where('code', $code)->exists();
    }
}