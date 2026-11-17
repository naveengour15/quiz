<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralcodesController extends Controller
{
    /**
     * Display referral codes.
     */
    public function index(Request $request): View
    {
        $query = DB::table('referral_codes')
            ->leftJoin(
                'users as creators',
                'creators.id',
                '=',
                'referral_codes.user_id'
            )
            ->leftJoin(
                'users as referred_users',
                'referred_users.id',
                '=',
                'referral_codes.referral_user_id'
            )
            ->leftJoin(
                'tenants',
                'tenants.id',
                '=',
                'referral_codes.tenant_id'
            )
            ->select([
                'referral_codes.*',

                // Creator User
                'creators.name as creator_name',
                'creators.mobile as creator_mobile',

                // Referred User
                'referred_users.name as referred_user_name',
                'referred_users.mobile as referred_user_mobile',

                // Tenant
                'tenants.name as tenant_name',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {

                $q->where('referral_codes.code', 'like', "%{$search}%")

                    ->orWhere(
                        'creators.name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'creators.mobile',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'referred_users.name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'referred_users.mobile',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'tenants.name',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        if ($request->filled('referral_point_provide')) {
            $query->where(
                'referral_codes.referral_point_provide',
                $request->input('referral_point_provide')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Used / Unused Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('used')) {
            $query->where(
                'referral_codes.used',
                $request->input('used')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where(
                'referral_codes.status',
                $request->input('status')
            );
        }
        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $referralCodes = $query
            ->orderByDesc('referral_codes.id')
            ->paginate(20)
            ->withQueryString();

        return view(
            'superadmin.referralcodes.index',
            compact('referralCodes')
        );
    }
}
