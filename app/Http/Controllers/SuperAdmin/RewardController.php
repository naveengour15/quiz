<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Redemption;
use App\Models\Reward;
use App\Models\RewardHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RewardController extends Controller
{
  

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        
        // Paginate rewards
        $rewards = Reward::latest()->paginate($perPage)->withQueryString();

        $rewardIds = $rewards->pluck('id');
        
        // Sirf 1 Query me current page ke saare coupons fetch karke group kar lo
        $allCoupons = RewardHistory::whereIn('reward_id', $rewardIds)
                        ->get()
                        ->groupBy('reward_id');

        // Transform the collection
        $rewards->getCollection()->transform(function ($reward) use ($allCoupons) {
            $coupons = $allCoupons->get($reward->id, collect());

            // Assigned Logic
            $assigned_count = $coupons->where('status', 'assigned')->count();
            if ($assigned_count === 0) {
                $assigned_count = $coupons->whereNotNull('user_id')->count();
            }

            $reward->stats = (object) [
                'total'            => $coupons->count(),
                'assigned'         => $assigned_count,
                'used'             => $coupons->where('status', 'used')->count(),
                'expired'          => $coupons->where('status', 'expired')->count(),
                'collected_points' => $coupons->where('status', 'used')->sum('deduct_point'),
            ];

            return $reward;
        });

        return view('superadmin.rewards.index', compact('rewards'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data = $this->validated($request);

        $data['points'] = $data['points'] ?? 0;
        $data['tenant_id'] =  1;

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('rewards', 's3');
        }
        
        $reward = Reward::create($data);
        
        if (!empty($reward->no_of_coupon) && $reward->no_of_coupon > 0) {
            $this->generateCoupons($reward, $reward->no_of_coupon);
        }
        
        AuditLog::log('Created reward', 'Rewards', $data['name']);
        return redirect()->route('superadmin.rewards.index')->with('success', 'Reward saved.');
    }

    public function update(Request $request, Reward $reward)
    {
        $data = $this->validated($request);
        if (isset($data['points'])) $data['coin_cost'] = $data['points'] ?? 0;
        
        if ($request->hasFile('photo')) {
            if ($reward->photo && Storage::disk('s3')->exists($reward->photo)) {
                Storage::disk('s3')->delete($reward->photo);
            }
            $data['photo'] = $request->file('photo')->store('rewards', 's3');
        }
        
        unset($data['no_of_coupon']);
        $reward->update($data);
        
        AuditLog::log('Updated reward', 'Rewards', $reward->name);
        return redirect()->route('superadmin.rewards.index')->with('success', 'Reward updated.');
    }

    /**
     * Reward details (short, for modal)
     */
    public function details(Reward $reward)
    {
        return response()->json([
            'success' => true,
            'reward' => $reward,
        ]);
    }

    /**
     * Coupons page (full list with stats)
     */
    public function coupons(Request $request, Reward $reward)
    {
          $perPage = $request->get('per_page', 10);
        $coupons = RewardHistory::where('reward_id', $reward->id)
            ->with(['assignedUser', 'usedUser'])
            ->latest()
            ->paginate($perPage)->withQueryString();
        
        $stats = [
            'total' => $coupons->count(),
            'available' => $coupons->where('status', 'available')->count(),
            'used' => $coupons->where('status', 'used')->count(),
            'expired' => $coupons->where('status', 'expired')->count(),
            'total_points' => $coupons->where('status', 'used')->sum('deduct_point'),
        ];
        
        return view('superadmin.rewards.coupons', compact('reward', 'coupons', 'stats'));
    }

    public function approve(Redemption $redemption)
    {
        $redemption->update(['status' => 'fulfilled']);
        AuditLog::log('Approved redemption', 'Rewards', 'TXN-' . $redemption->id);
        return back()->with('success', 'Redemption approved.');
    }

    public function reject(Redemption $redemption)
    {
        $redemption->update(['status' => 'rejected']);
        AuditLog::log('Rejected redemption', 'Rewards', 'TXN-' . $redemption->id);
        return back()->with('success', 'Redemption rejected.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:191',
            'category' => 'required|string|in:Mobile Recharge,Bill Payment,Voucher',
            'partner' => 'nullable|string',
            'points' => 'nullable|integer|min:0',
            'coin_cost' => 'nullable|integer|min:0',
            'no_of_coupon' => 'nullable|integer|min:0',
            'expire_duration' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive,out_of_stock',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:rewards,id',
            'status' => 'required|in:active,inactive,out_of_stock',
        ]);

        $reward = Reward::findOrFail($request->id);
        $reward->update(['status' => $request->status]);
        AuditLog::log('Updated reward status', 'Rewards', $reward->name);

        return response()->json([
            'success' => true,
            'message' => 'Reward status updated successfully.',
        ]);
    }

   
    public function addCoupons(Request $request, Reward $reward)
    {
        $request->validate([
            'no_of_coupon' => 'required|integer|min:1|max:1000',
            'expire_duration' => 'nullable|integer|min:0',
        ]);
        
        $count = (int)$request->no_of_coupon;
        $expireDuration = $request->expire_duration ? (int)$request->expire_duration : null;
        
        $this->generateCoupons($reward, $count, $expireDuration);
        
        // Update reward's no_of_coupon count
        $reward->update([
            'no_of_coupon' => ($reward->no_of_coupon ?? 0) + $count,
            'expire_duration' => $expireDuration ?? 0 ,
        ]);
        
        AuditLog::log('Added coupons', 'Rewards', $reward->name . ' (' . $count . ' coupons)');
        
        return redirect()
            ->route('superadmin.rewards.coupons', $reward)
            ->with('success', $count . ' coupons generated successfully.');
    }


    private function generateCoupons(Reward $reward, int $count, ?int $expireDuration = null): void
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $reward->name), 0, 2));
        $prefix = $prefix ?: 'RW';
        
        $generated = 0;
        $maxAttempts = $count * 20;
        $attempts = 0;
        
        // Use provided expire_duration or fallback to reward's default
        $duration = $expireDuration ?? $reward->expire_duration;
        
        $expireDate = null;
        if ($duration && $duration > 0) {
            $expireDate = now()->addDays((int)$duration);
        }
        
        while ($generated < $count && $attempts < $maxAttempts) {
            $code = $prefix . random_int(1000, 9999);
            
            if (!RewardHistory::where('code', $code)->exists()) {
                RewardHistory::create([
                    'tenant_id' => $reward->tenant_id ?? 1,
                    'reward_id' => $reward->id,
                    'reward_type' => 'voucher',
                    'code' => $code,
                    'status' => 'pending',
                    'expire_date' => $expireDate,
                    'deduct_point' => $reward->points ?? $reward->coin_cost ?? 0,
                ]);
                $generated++;
            }
            $attempts++;
        }
    }

}