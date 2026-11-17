<?php

namespace App\Http\Controllers\fsrapp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Banner;
use App\Models\RedeemRequestOffDay;
use App\Interfaces\fsrapp\EGiftvoucherRepositoryInterface;

class WalletController extends Controller
{
    protected $giftVoucherRepo;

    public function __construct(EGiftvoucherRepositoryInterface $giftVoucherRepo)
    {
        $this->giftVoucherRepo = $giftVoucherRepo;
    }

    public function index(Request $request)
    {
        $userId = Auth::id() ?? session('fsr_user_id');
        if (!$userId) {
            return redirect()->route('fsrapp.login');
        }
        $user = Auth::user() ?: User::find($userId);
        if (!$user) {
            return redirect()->route('fsrapp.login');
        }

        $walletBalance = $user->wallet_balance ?? 0;
        $perPage = 10;

        $walletHistory = WalletHistory::where('user_id', $user->id)
            ->select([
                'id',
                'user_id',
                'amount',
                'type',
                'transaction_type',
                'remarks',
                'created_at'
            ])
            ->latest('id')
            ->paginate($perPage);

        // AJAX pagination (Load more / infinite scroll) skips banners and full page rendering
        if ($request->ajax()) {
            return response()->json([
                'html' => view('fsrapp.wallet.partials.transactions', compact('walletHistory'))->render(),
                'next_page' => $walletHistory->nextPageUrl(),
                'has_more' => $walletHistory->hasMorePages()
            ]);
        }

        $totalCount = $walletHistory->total();
        $banners = Banner::where('status', 'active')->latest('id')->get();    

        return view('fsrapp.wallet.index', compact('user', 'walletBalance', 'walletHistory', 'totalCount', 'banners'));
    }

    public function redeem()
    {
        $userId = Auth::id() ?? session('fsr_user_id');
        if (!$userId) {
            return redirect()->route('fsrapp.login');
        }
        $user = Auth::user() ?: User::find($userId);
        if (!$user) {
            return redirect()->route('fsrapp.login');
        }

        $walletBalance = $user->wallet_balance ?? 0;
        $tenantId = $user->tenant_id ?? 1;

        // 1. Static Network & Zone Options (column selective)
        $simNetworks = \App\Models\SimNetwork::where('status', 'active')->select(['id', 'name'])->get();
        $electricityZones = \App\Models\ElectricityZone::where('status', 'active')->select(['id', 'zone'])->get();

        // 2. User's Recent Redeem Requests (column selective, latest 30)
        $redeemHistories = \App\Models\RedeemRequest::where('user_id', $user->id)
            ->select([
                'id',
                'request_type',
                'status',
                'mobile',
                'company',
                'consumer_number',
                'redeem_amount',
                'approver_note',
                'created_at'
            ])
            ->latest('id')
            ->take(30)
            ->get();

        // 3. Available E-Gift Vouchers
        $eGiftVouchers = $this->giftVoucherRepo->getAvailableVouchers($tenantId);

        // 4. Processing Fee
        $processingFee = \App\Models\ProcessingFee::where('tenant_id', $tenantId)->select(['voucher'])->first();
        $voucherFeePercent = $processingFee ? (float) $processingFee->voucher : 0.0;

        // 5. Active Banners (column selective)
        $banners = Banner::where('status', 'active')
            ->select(['id', 'banner_file', 'url', 'type'])
            ->latest('id')
            ->get();

        // 6. Redeem Request Time Restriction Check
        $setting = \App\Models\QuizSetting::select(['redeem_request_start_time', 'redeem_request_end_time','redeem_request_days'])->first();
        $startTime = \Carbon\Carbon::parse($setting->redeem_request_start_time ?? '11:00:00');
        $endTime = \Carbon\Carbon::parse($setting->redeem_request_end_time ?? '21:00:00');
        $currentTime = now();
        $startToday = $startTime->copy()->setDate($currentTime->year, $currentTime->month, $currentTime->day);
        $endToday = $endTime->copy()->setDate($currentTime->year, $currentTime->month, $currentTime->day);
        $isRequestTime = $currentTime->between($startToday, $endToday);
        $startTimeFormatted = $startTime->format('h:i A');
        $endTimeFormatted = $endTime->format('h:i A');

        
        // 3. Check karo ki aaj ka din OFF toh nahi hai
        $currentYear = now()->year;
        $currentMonth = now()->month;
        $currentDate = now()->day;
        $currentMonthName = now()->format('M'); // e.g., Oct, Nov
        
        $isDayOff = false;
        $offDayMessage = '';

        // Active off days fetch karo current year aur month ke liye
        $offDays = \App\Models\RedeemRequestOffDay::where('tenant_id', $tenantId)
            ->where('year', $currentYear)
            ->where('month', $currentMonth)
            ->where('status', 'active')
            ->get();

        // Sirf off dates ka array banao
        $offDatesForCurrentMonth = [];
        foreach ($offDays as $offDay) {
            $datesArray = is_string($offDay->date) ? json_decode($offDay->date, true) : $offDay->date;
            if (is_array($datesArray)) {
                $offDatesForCurrentMonth = array_merge($offDatesForCurrentMonth, array_filter($datesArray, 'is_numeric'));
            }
        }

        // ✅ Sirf Aaj ki Date Check karo
        if (in_array($currentDate, $offDatesForCurrentMonth)) {
            $isDayOff = true;
            
            // Simple message sirf aaj ki date ke liye
            $offDayMessage = "Redeem requests are closed today, <strong>{$currentDate} {$currentMonthName}</strong>. Please try on another allowed day.";
        }

        return view('fsrapp.redeem.index', compact(
            'user',
            'walletBalance',
            'simNetworks',
            'electricityZones',
            'redeemHistories',
            'eGiftVouchers',
            'voucherFeePercent',
            'banners',
            'isRequestTime',
            'startTimeFormatted',
            'endTimeFormatted',
            'isDayOff',             // ✅ Naya: true agar aaj OFF hai
            'offDayMessage'            // ✅ Naya: OFF wale din ka naam (e.g., "Monday")
        ));
    }

    

    public function egiftvoucherHistory()
    {
        $userId = Auth::id() ?? session('fsr_user_id');
        if (!$userId) {
            return redirect()->route('fsrapp.login');
        }

        $user = Auth::user() ?: \App\Models\User::find($userId);
        if (!$user) {
            return redirect()->route('fsrapp.login');
        }

        $tenantId = $user->tenant_id ?? 1;

        // 1. Fetch e_gift_voucher_requests with selective columns and eager relations
        $userVoucherRequests = \App\Models\EGiftVoucherRequest::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->with('voucher:id,name,amount,image')
            ->select([
                'id',
                'tenant_id',
                'user_id',
                'e_gift_voucher_id',
                'voucher_amount',
                'fee_percent',
                'fee_amount',
                'total_amount',
                'status',
                'voucher_code',
                'url',
                'image',
                'remark',
                'approve_date',
                'created_at',
                'updated_at'
            ])
            ->latest('id')
            ->get();

        // Pre-group by status in controller to offload Blade template processing
        $pendingRequests = $userVoucherRequests->where('status', 'pending');
        $approvedRequests = $userVoucherRequests->where('status', 'approved')->sortByDesc('approve_date');
        $cancelledRequests = $userVoucherRequests->whereIn('status', ['rejected', 'cancelled']);

        // 2. Legacy vouchers with eager loaded voucher to prevent N+1 queries
        $vouchers = \App\Models\VoucherHistory::where('assign_to_user_id', $user->id)
            ->with('voucher:id,name,amount,image')
            ->select([
                'id',
                'voucher_id',
                'assign_to_user_id',
                'code',
                'status',
                'voucher_point',
                'used_date',
                'expire_date',
                'created_at'
            ])
            ->latest('id')
            ->get();

        $totalCount = $userVoucherRequests->count() + $vouchers->count();

        return view('fsrapp.redeem.e-gift-vouchers.history', compact(
            'userVoucherRequests',
            'vouchers',
            'pendingRequests',
            'approvedRequests',
            'cancelledRequests',
            'totalCount'
        ));
    }

    public function redeemHistory()
    {
        return view('fsrapp.redeem.history');
    }

    public function submitRedeem(Request $request)
    {
        $userId = Auth::id() ?? session('fsr_user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Please login to submit a request.']);
        }

        $type = $request->input('type');
        $amount = (int) $request->input('amount');

        // Pre-validate input parameters before opening database transaction
        if ($type === 'mobile') {
            $mobile = $request->input('mobile');
            $company = $request->input('company');
            if (empty($mobile) || !preg_match('/^[0-9]{10}$/', $mobile)) {
                return response()->json(['success' => false, 'message' => 'Please enter a valid 10-digit mobile number.']);
            }
            if (empty($company)) {
                return response()->json(['success' => false, 'message' => 'SIM operator is required.']);
            }
        } elseif ($type === 'electricity') {
            $consumerNumber = $request->input('consumer_number');
            $zoneId = $request->input('zone_id');
            if (empty($consumerNumber) || empty($zoneId)) {
                return response()->json(['success' => false, 'message' => 'Consumer number and zone are required.']);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid request type.']);
        }

        // Atomic transaction with pessimistic row lock to prevent race condition (double redemption)
        return \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $type, $amount, $request) {
            $user = User::where('id', $userId)->lockForUpdate()->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Please login to submit a request.']);
            }

            $walletBalance = (float) ($user->wallet_balance ?? 0);
            if ($walletBalance < 250) {
                return response()->json(['success' => false, 'message' => 'Minimum 250 points required.']);
            }

            if ($amount <= 0 || $walletBalance < $amount) {
                return response()->json(['success' => false, 'message' => 'Insufficient points or invalid amount.']);
            }

            $redeemRequest = new \App\Models\RedeemRequest();
            $redeemRequest->user_id = $user->id;
            $redeemRequest->tenant_id = $user->tenant_id ?? 1;
            $redeemRequest->redeem_amount = $amount;
            $redeemRequest->status = 'pending';

            if ($type === 'mobile') {
                $redeemRequest->request_type = 'Mobile Recharge';
                $redeemRequest->mobile = $request->input('mobile');
                $redeemRequest->company = $request->input('company');
            } else {
                $redeemRequest->request_type = 'Electricity Bill';
                $redeemRequest->consumer_number = $request->input('consumer_number');
                $redeemRequest->zone_id = $request->input('zone_id');
            }

            $user->wallet_balance -= $amount;
            $user->save();

            \App\Models\WalletHistory::create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id ?? 1,
                'type' => 'debit',
                'amount' => $amount,
                'title' => 'Redeem Request - ' . $redeemRequest->request_type,
                'description' => 'Points deducted for ' . $redeemRequest->request_type,
            ]);

            $redeemRequest->save();

            return response()->json(['success' => true, 'message' => 'Request submitted successfully!']);
        });
    }
}
