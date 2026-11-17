<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Redemption;
use App\Models\Voucher;
use App\Models\VoucherHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = getTenantId();
        $perPage = $request->get('per_page', 10);
        
        $vouchers = Voucher::where('tenant_id', $tenantId)->latest()->paginate($perPage)->withQueryString();
        $rewardIds = $vouchers->pluck('id');
        
        $allCoupons = VoucherHistory::where('tenant_id', $tenantId)
                        ->whereIn('voucher_id', $rewardIds)
                        ->get()
                        ->groupBy('voucher_id');

        $vouchers->getCollection()->transform(function ($voucher) use ($allCoupons) {
            $coupons = $allCoupons->get($voucher->id, collect());

            $assigned_count = $coupons->where('status', 'assigned')->count();
            if ($assigned_count === 0) {
                $assigned_count = $coupons->whereNotNull('user_id')->count();
            }

            $voucher->stats = (object) [
                'total'            => $coupons->count(),
                'assigned'         => $assigned_count,
                'used'             => $coupons->where('status', 'used')->count(),
                'expired'          => $coupons->where('status', 'expired')->count(),
                'collected_points' => $coupons->where('status', 'used')->sum('deduct_point'),
            ];

            return $voucher;
        });

        return view('admin.voucher.index', compact('vouchers'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $tenantId = getTenantId();

        $data['points'] = $data['points'] ?? 0;
        $data['tenant_id'] = $tenantId;

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            if ($file && $file->isValid()) {
                $imageName = uniqid(time(), true) . '.webp';
                $path = "vouchers/{$imageName}";
                try {
                    $manager = new ImageManager(new Driver);
                    $image = $manager->decode($file);
                    if ($image->width() > 1920) {
                        $image->scaleDown(width: 1920);
                    }
                    $encoded = $image->encode(new WebpEncoder(90));
                    Storage::disk('s3')->put($path, (string) $encoded, 'public');
                } catch (\Exception $e) {
                    Log::warning('Admin voucher photo store fallback: ' . $e->getMessage());
                    Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()), 'public');
                }
                $data['photo'] = $path;
            }
        }
        
        $voucher = Voucher::create($data);
        
        if (!empty($data['no_of_coupon']) && $data['no_of_coupon'] > 0) {
            $this->generateCoupons($voucher, $data['no_of_coupon']);
        }
        
        AuditLog::log('Created voucher', 'Vouchers', $data['name']);
        return redirect()->route('admin.voucher.index')->with('success', 'Voucher saved.');
    }

    public function update(Request $request, Voucher $voucher)
    {
        $tenantId = getTenantId();
        if ($voucher->tenant_id != $tenantId) {
            abort(403, 'Unauthorized tenant access.');
        }

        $data = $this->validated($request);
        if (isset($data['points'])) $data['coin_cost'] = $data['points'] ?? 0;
        
        if ($request->hasFile('photo')) {
            if ($voucher->photo && Storage::disk('s3')->exists($voucher->photo)) {
                Storage::disk('s3')->delete($voucher->photo);
            }
            $file = $request->file('photo');
            if ($file && $file->isValid()) {
                $imageName = uniqid(time(), true) . '.webp';
                $path = "vouchers/{$imageName}";
                try {
                    $manager = new ImageManager(new Driver);
                    $image = $manager->decode($file);
                    if ($image->width() > 1920) {
                        $image->scaleDown(width: 1920);
                    }
                    $encoded = $image->encode(new WebpEncoder(90));
                    Storage::disk('s3')->put($path, (string) $encoded, 'public');
                } catch (\Exception $e) {
                    Log::warning('Admin voucher photo update fallback: ' . $e->getMessage());
                    Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()), 'public');
                }
                $data['photo'] = $path;
            }
        }
        
        unset($data['no_of_coupon']);
        $voucher->update($data);
        
        AuditLog::log('Updated voucher', 'Vouchers', $voucher->name);
        return redirect()->route('admin.voucher.index')->with('success', 'Voucher updated.');
    }

    public function details(Voucher $voucher)
    {
        $tenantId = getTenantId();
        if ($voucher->tenant_id != $tenantId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        return response()->json([
            'success' => true,
            'voucher' => $voucher,
        ]);
    }

    public function coupons(Request $request, Voucher $voucher)
    {
        $tenantId = getTenantId();
        if ($voucher->tenant_id != $tenantId) {
            abort(403, 'Unauthorized tenant access.');
        }

        $perPage = $request->get('per_page', 10);
        $coupons = VoucherHistory::where('tenant_id', $tenantId)
            ->where('voucher_id', $voucher->id)
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
        
        return view('admin.voucher.coupons', compact('voucher', 'coupons', 'stats'));
    }

    public function approve(Redemption $redemption)
    {
        $tenantId = getTenantId();
        if ($redemption->tenant_id != $tenantId) {
            abort(403, 'Unauthorized tenant access.');
        }

        $redemption->update(['status' => 'fulfilled']);
        AuditLog::log('Approved redemption', 'Vouchers', 'TXN-' . $redemption->id);
        return back()->with('success', 'Redemption approved.');
    }

    public function reject(Redemption $redemption)
    {
        $tenantId = getTenantId();
        if ($redemption->tenant_id != $tenantId) {
            abort(403, 'Unauthorized tenant access.');
        }

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
            'id' => 'required|exists:vouchers,id',
            'status' => 'required|in:active,inactive,out_of_stock',
        ]);

        $tenantId = getTenantId();
        $voucher = Voucher::where('tenant_id', $tenantId)->findOrFail($request->id);
        $voucher->update(['status' => $request->status]);
        AuditLog::log('Updated voucher status', 'Vouchers', $voucher->name);

        return response()->json([
            'success' => true,
            'message' => 'Voucher status updated successfully.',
        ]);
    }

    public function addCoupons(Request $request, Voucher $voucher)
    {
        $tenantId = getTenantId();
        if ($voucher->tenant_id != $tenantId) {
            abort(403, 'Unauthorized tenant access.');
        }

        $request->validate([
            'no_of_coupon' => 'required|integer|min:1|max:1000',
            'expire_duration' => 'nullable|integer|min:0',
        ]);
        
        $count = (int)$request->no_of_coupon;
        $expireDuration = $request->expire_duration ? (int)$request->expire_duration : null;
        
        $this->generateCoupons($voucher, $count, $expireDuration);
        
        $voucher->update([
            'no_of_coupon' => ($voucher->no_of_coupon ?? 0) + $count,
            'expire_duration' => $expireDuration ?? 0,
        ]);
        
        AuditLog::log('Added coupons', 'Vouchers', $voucher->name . ' (' . $count . ' coupons)');
        
        return redirect()
            ->route('admin.voucher.coupons', $voucher)
            ->with('success', $count . ' coupons generated successfully.');
    }

    private function generateCoupons(Voucher $voucher, int $count, ?int $expireDuration = null): void
    {
        $tenantId = getTenantId();
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $voucher->name), 0, 2));
        $prefix = $prefix ?: 'RW';
        
        $generated = 0;
        $maxAttempts = $count * 20;
        $attempts = 0;
        
        $duration = $expireDuration ?? $voucher->expire_duration;
        
        while ($generated < $count && $attempts < $maxAttempts) {
            $code = $prefix . random_int(1000, 9999);
            
            if (!VoucherHistory::where('code', $code)->exists()) {
                VoucherHistory::create([
                    'tenant_id' => $tenantId,
                    'voucher_id' => $voucher->id,
                    'voucher_type' => 'voucher',
                    'code' => $code,
                    'expire_duration' => $duration,
                    'status' => 'pending',
                    'deduct_point' => $voucher->points ?? $voucher->coin_cost ?? 0,
                ]);
                $generated++;
            }
            $attempts++;
        }
    }

    public function destroy(Voucher $voucher) 
    {
        $tenantId = getTenantId();
        if ($voucher->tenant_id != $tenantId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized tenant access.'], 403);
        }

        $voucherName = $voucher->name;
        $voucher->delete(); 
        
        AuditLog::log('Deleted voucher', 'Vouchers', $voucherName);
        
        return response()->json([
            'success' => true,
            'message' => 'Voucher deleted successfully.',
        ]);
    }
}
