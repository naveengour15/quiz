<?php

namespace App\Http\Controllers\androidapi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\androidapi\Traits\ResolvesAndroidUser;
use App\Interfaces\fsrapp\EGiftvoucherRepositoryInterface;
use App\Models\User;
use App\Models\WalletHistory;
use App\Models\RedeemRequest;
use App\Models\SimNetwork;
use App\Models\ElectricityZone;
use App\Models\VoucherHistory;
use App\Models\Reward;
use App\Models\Redemption;
use App\Models\ProcessingFee;
use App\Models\QuizSetting;
use App\Models\RedeemRequestOffDay;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    use ResolvesAndroidUser;

    protected $giftVoucherRepo;

    public function __construct(EGiftvoucherRepositoryInterface $giftVoucherRepo = null)
    {
        $this->giftVoucherRepo = $giftVoucherRepo ?: app(EGiftvoucherRepositoryInterface::class);
    }

    public function getWallet(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $tenantId = $user->tenant_id ?? 1;
        $walletBalance = $user->wallet_balance ?? 0;
        $walletHistory = WalletHistory::where('user_id', $user->id)->orderBy('id', 'desc')->paginate($request->get('per_page', 15));
        $redeemStatus = $this->getRedeemStatus($tenantId);

        return response()->json([
            'success' => true,
            'wallet_balance' => $walletBalance,
            'history' => $walletHistory,
            'is_request_time' => $redeemStatus['is_request_time'],
            'start_time_formatted' => $redeemStatus['start_time_formatted'],
            'end_time_formatted' => $redeemStatus['end_time_formatted'],
            'is_day_off' => $redeemStatus['is_day_off'],
            'off_day_message' => $redeemStatus['off_day_message'],
            'request_time_message' => $redeemStatus['request_time_message'],
            'redeem_voucher_settings' => \App\Models\RedeemVoucherSetting::where('tenant_id', $tenantId)->get(),
        ]);
    }

    public function getRedeemData(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $tenantId = $user->tenant_id ?? 1;
        $walletBalance = $user->wallet_balance ?? 0;
        $simNetworks = SimNetwork::where('status', 'active')->orderBy('name', 'asc')->get();
        $electricityZones = ElectricityZone::where('status', 'active')->orderBy('zone', 'asc')->get();
        $redeemHistories = RedeemRequest::where('user_id', $user->id)->orderBy('id', 'desc')->get();
        $vouchers = VoucherHistory::where('assign_to_user_id', $user->id)->orderBy('id', 'desc')->get();

        $eGiftVouchers = $this->giftVoucherRepo->getAvailableVouchers($tenantId);
        $userVoucherRequests = $this->giftVoucherRepo->getUserVoucherRequests($user->id, $tenantId);
        $processingFee = ProcessingFee::where('tenant_id', $tenantId)->first();
        $voucherFeePercent = $processingFee ? (float) $processingFee->voucher : 0.0;
        $redeemStatus = $this->getRedeemStatus($tenantId);

        return response()->json([
            'success' => true,
            'wallet_balance' => $walletBalance,
            'sim_networks' => $simNetworks,
            'electricity_zones' => $electricityZones,
            'redeem_history' => $redeemHistories,
            'vouchers' => $vouchers,
            'e_gift_vouchers' => $eGiftVouchers,
            'user_voucher_requests' => $userVoucherRequests,
            'voucher_fee_percent' => $voucherFeePercent,
            'is_request_time' => $redeemStatus['is_request_time'],
            'start_time_formatted' => $redeemStatus['start_time_formatted'],
            'end_time_formatted' => $redeemStatus['end_time_formatted'],
            'is_day_off' => $redeemStatus['is_day_off'],
            'off_day_message' => $redeemStatus['off_day_message'],
            'request_time_message' => $redeemStatus['request_time_message'],
            'redeem_voucher_settings' => \App\Models\RedeemVoucherSetting::where('tenant_id', $tenantId)->get(),
        ]);
    }

    private function getRedeemStatus($tenantId)
    {
        $setting = QuizSetting::select(['redeem_request_start_time', 'redeem_request_end_time', 'redeem_request_days'])->first();
        $startTimeStr = $setting->redeem_request_start_time ?? '11:00:00';
        $endTimeStr = $setting->redeem_request_end_time ?? '21:00:00';

        $startTime = Carbon::parse($startTimeStr);
        $endTime = Carbon::parse($endTimeStr);
        $currentTime = now();

        $startToday = $startTime->copy()->setDate($currentTime->year, $currentTime->month, $currentTime->day);
        $endToday = $endTime->copy()->setDate($currentTime->year, $currentTime->month, $currentTime->day);
        $isRequestTime = $currentTime->between($startToday, $endToday);

        $startTimeFormatted = $startTime->format('h:i A');
        $endTimeFormatted = $endTime->format('h:i A');

        $currentYear = (int) $currentTime->year;
        $currentMonth = (int) $currentTime->month;
        $currentDate = (int) $currentTime->day;
        $currentMonthName = $currentTime->format('M');
        $todayDayName = $currentTime->format('l');

        $isDayOff = false;
        $offDayMessage = '';

        // 1. Check weekly days off from QuizSetting::redeem_request_days
        $savedDays = $setting->redeem_request_days ?? [];
        if (!empty($savedDays) && is_array($savedDays)) {
            foreach ($savedDays as $dayData) {
                if (isset($dayData['day']) && strcasecmp($dayData['day'], $todayDayName) === 0) {
                    if (($dayData['status'] ?? 'on') === 'off') {
                        $isDayOff = true;
                        $offDayMessage = "Dhyan dein: {$todayDayName} ko redeem requests band rehti hain. Kripya kisi aur din (Allowed Days) mein wapas aayein.";
                        break;
                    }
                }
            }
        }

        // 2. Check monthly off days from /superadmin/redeem-off-days (RedeemRequestOffDay)
        $offDays = RedeemRequestOffDay::withoutGlobalScopes()
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhereNull('tenant_id')
                  ->orWhere('tenant_id', 1)
                  ->orWhere('tenant_id', 0);
            })
            ->where('year', $currentYear)
            ->where('month', $currentMonth)
            ->where(function ($q) {
                $q->where('status', 'active')
                  ->orWhereNull('status');
            })
            ->get();

        $offDatesForCurrentMonth = [];
        foreach ($offDays as $offDay) {
            $rawDates = $offDay->date;
            if (is_string($rawDates)) {
                $decoded = json_decode($rawDates, true);
                $rawDates = is_array($decoded) ? $decoded : explode(',', $rawDates);
            }
            if (is_array($rawDates)) {
                foreach ($rawDates as $d) {
                    $dInt = (int) trim((string) $d);
                    if ($dInt > 0) {
                        $offDatesForCurrentMonth[] = $dInt;
                    }
                }
            }
        }
        $offDatesForCurrentMonth = array_values(array_unique($offDatesForCurrentMonth));

        if (in_array((int) $currentDate, $offDatesForCurrentMonth, true)) {
            $isDayOff = true;
            $offDayMessage = "Redeem requests are closed today, {$currentDate} {$currentMonthName}. Please try on another allowed day.";
        }

        $requestTimeMessage = "Aap redeem request sirf {$startTimeFormatted} se {$endTimeFormatted} ke beech hi kar sakte hain. Kripya is samay ke dauran wapis aayein.";

        return [
            'is_request_time' => $isRequestTime,
            'start_time_formatted' => $startTimeFormatted,
            'end_time_formatted' => $endTimeFormatted,
            'is_day_off' => $isDayOff,
            'off_day_message' => $offDayMessage,
            'request_time_message' => $requestTimeMessage,
        ];
    }

    public function getRedeemOptions(Request $request)
    {
        $simNetworks = SimNetwork::where('status', 'active')->orderBy('name', 'asc')->get(['id', 'name', 'code']);
        $electricityZones = ElectricityZone::where('status', 'active')->orderBy('zone', 'asc')->get(['id', 'zone', 'zone_code']);

        return response()->json([
            'success' => true,
            'sim_networks' => $simNetworks,
            'electricity_zones' => $electricityZones
        ]);
    }

    public function requestVoucher(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please login to claim vouchers.'], 401);
        }

        $tenantId = $user->tenant_id ?? 1;
        $redeemStatus = $this->getRedeemStatus($tenantId);

        if ($redeemStatus['is_day_off']) {
            return response()->json([
                'success' => false,
                'message' => $redeemStatus['off_day_message'] ?: 'Redeem requests are closed today. Please try on another allowed day.'
            ]);
        }

        if (!$redeemStatus['is_request_time']) {
            return response()->json([
                'success' => false,
                'message' => $redeemStatus['request_time_message'] ?: 'Redeem requests are restricted at this time.'
            ]);
        }

        $request->validate([
            'voucher_id' => 'required|integer|exists:e_gift_vouchers,id'
        ]);

        $result = $this->giftVoucherRepo->createVoucherRequest($user->id, (int) $request->voucher_id, $tenantId);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function submitRedeem(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please login to submit a request.'], 401);
        }

        $tenantId = $user->tenant_id ?? 1;
        $redeemStatus = $this->getRedeemStatus($tenantId);

        if ($redeemStatus['is_day_off']) {
            return response()->json([
                'success' => false,
                'message' => $redeemStatus['off_day_message'] ?: 'Redeem requests are closed today. Please try on another allowed day.'
            ]);
        }

        if (!$redeemStatus['is_request_time']) {
            return response()->json([
                'success' => false,
                'message' => $redeemStatus['request_time_message'] ?: 'Redeem requests are restricted at this time.'
            ]);
        }

        $walletBalance = $user->wallet_balance ?? 0;
        if ($walletBalance < 250) {
            return response()->json(['success' => false, 'message' => 'Minimum 250 points are required to make a redeem request. Isse kam points withdraw nahi kar sakte.']);
        }

        $type = $request->input('type');
        $amount = (int) $request->input('amount');

        if ($amount < 250) {
            return response()->json(['success' => false, 'message' => 'Minimum redeem request amount is 250 points. Isse kam points withdraw nahi kar sakte.']);
        }

        if ($amount > 5000) {
            return response()->json(['success' => false, 'message' => 'Maximum redeem request amount is 5000 points.']);
        }

        if ($walletBalance < $amount) {
            return response()->json(['success' => false, 'message' => 'Insufficient points balance.']);
        }

        $redeemRequest = new RedeemRequest();
        $redeemRequest->user_id = $user->id;
        $redeemRequest->tenant_id = $user->tenant_id ?? 1;
        $redeemRequest->redeem_amount = $amount;
        $redeemRequest->status = 'pending';

        if ($type === 'mobile') {
            $redeemRequest->request_type = 'Mobile Recharge';
            $redeemRequest->mobile = $request->input('mobile');
            $redeemRequest->company = $request->input('company');
            if (empty($redeemRequest->mobile) || !preg_match('/^[0-9]{10}$/', $redeemRequest->mobile)) {
                return response()->json(['success' => false, 'message' => 'Please enter a valid 10-digit mobile number.']);
            }
            if (empty($redeemRequest->company)) {
                return response()->json(['success' => false, 'message' => 'SIM operator is required.']);
            }
        } elseif ($type === 'electricity') {
            $redeemRequest->request_type = 'Electricity Bill';
            $redeemRequest->consumer_number = $request->input('consumer_number');
            $zoneInput = $request->input('zone_id');
            if (empty($redeemRequest->consumer_number) || empty($zoneInput)) {
                return response()->json(['success' => false, 'message' => 'Consumer number and zone are required.']);
            }
            if (!is_numeric($zoneInput)) {
                $foundZone = ElectricityZone::where('zone', $zoneInput)->orWhere('zone_code', $zoneInput)->first();
                $redeemRequest->zone_id = $foundZone ? $foundZone->id : 0;
            } else {
                $redeemRequest->zone_id = (int) $zoneInput;
            }
            if (empty($redeemRequest->zone_id)) {
                return response()->json(['success' => false, 'message' => 'Invalid electricity zone selected.']);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid request type.']);
        }

        $user->wallet_balance -= $amount;
        $user->save();

        WalletHistory::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id ?? 1,
            'type' => 'debit',
            'amount' => $amount,
            'title' => 'Redeem Request - ' . $redeemRequest->request_type,
            'description' => 'Points deducted for ' . $redeemRequest->request_type,
        ]);

        $redeemRequest->save();

        return response()->json(['success' => true, 'message' => 'Redeem request submitted successfully!']);
    }

    public function balance(Request $request)
    {
        return $this->getWallet($request);
    }

    public function history(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => WalletHistory::where('user_id', $user->id)->latest('id')->paginate($request->get('per_page', 15)),
        ]);
    }

    public function rewards(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => Reward::where('status', 'active')->get(),
        ]);
    }

    public function redemptions(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => Redemption::where('user_id', $user->id)->latest('id')->paginate(20),
        ]);
    }

    public function redeemReward(Request $request)
    {
        $request->validate(['reward_id' => 'required|integer', 'coins' => 'required|integer|min:1']);
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return DB::transaction(function () use ($request, $user) {
            $reward = Reward::where('status', 'active')->find($request->integer('reward_id'));
            $coins = $request->integer('coins');
            if (!$reward) {
                return response()->json(['success' => false, 'message' => 'Reward not available'], 404);
            }
            if ($user->wallet_balance < $coins) {
                return response()->json(['success' => false, 'message' => 'Insufficient wallet balance'], 422);
            }

            $redemption = Redemption::create([
                'tenant_id' => $user->tenant_id ?? 1,
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'coins' => $coins,
                'status' => 'pending',
            ]);
            $user->decrement('wallet_balance', $coins);
            WalletHistory::create([
                'tenant_id' => $user->tenant_id ?? 1,
                'user_id' => $user->id,
                'amount' => $coins,
                'type' => 'debit',
                'transaction_type' => 'reward_redemption',
                'reference_id' => (string) $redemption->id,
                'remarks' => "Redeemed for {$reward->name}",
            ]);

            return response()->json(['success' => true, 'message' => 'Redemption request submitted', 'data' => $redemption]);
        });
    }
}