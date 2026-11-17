<?php

namespace App\Http\Controllers\SuperAdmin;

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
    /**
     * Display vouchers list with statistics
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        
        // Fetch vouchers with search and status filters
        $vouchers = Voucher::where('tenant_id', getSuperAdminTenantId())
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('partner', 'like', '%' . $request->search . '%');
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
        
        // Fetch all coupon histories for current page vouchers
        $rewardIds = $vouchers->pluck('id');
        $allCoupons = VoucherHistory::where('tenant_id', getSuperAdminTenantId())
            ->whereIn('voucher_id', $rewardIds)
            ->get()
            ->groupBy('voucher_id');

        // Calculate statistics for each voucher
        $vouchers->getCollection()->transform(function ($voucher) use ($allCoupons) {
            $coupons = $allCoupons->get($voucher->id, collect());

            // Calculate assigned count
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

        return view('superadmin.voucher.index', compact('vouchers'));
    }

    /**
     * Store new voucher
     */
    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['points'] = $data['points'] ?? 0;
        $data['tenant_id'] = getSuperAdminTenantId();

        // Handle photo upload
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
                    Log::warning('Voucher photo store fallback: ' . $e->getMessage());
                    Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()), 'public');
                }
                $data['photo'] = $path;
            }
        }
        
        $voucher = Voucher::create($data);
        
        // Determine if auto-generate or manual codes
        if ($this->isSelfVoucher($voucher->partner)) {
            // Auto-generate coupons for self vouchers
            if (!empty($data['no_of_coupon']) && $data['no_of_coupon'] > 0) {
                $this->generateCoupons($voucher, $data['no_of_coupon']);
            }
        } else {
            // Manual codes for external brands
            $manualCodes = $request->input('manual_codes', []);
            $this->createManualCodes($voucher, $manualCodes);
        }
        
        AuditLog::log('Created voucher', 'Vouchers', $data['name']);
        return redirect()->route('superadmin.voucher.index')->with('success', 'Voucher saved.');
    }

    /**
     * Update existing voucher
     */
    public function update(Request $request, Voucher $voucher)
    {
        $data = $this->validated($request);
        
        // Handle photo upload and deletion
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
                    Log::warning('Voucher photo update fallback: ' . $e->getMessage());
                    Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()), 'public');
                }
                $data['photo'] = $path;
            }
        }
        
        // Remove no_of_coupon from update data (handled separately)
        unset($data['no_of_coupon']);
        $voucher->update($data);
        
        // Handle manual codes for external brands
        if (!$this->isSelfVoucher($voucher->partner)) {
            // Delete removed codes
            $deleteCodes = $request->input('delete_codes', []);
            if (!empty($deleteCodes)) {
                VoucherHistory::where('voucher_id', $voucher->id)
                    ->whereIn('code', $deleteCodes)
                    ->whereIn('status', ['pending', 'available'])
                    ->delete();
            }

            // Add new codes
            $newCodes = $request->input('new_manual_codes', []);
            if (!empty($newCodes)) {
                $this->createManualCodes($voucher, $newCodes);
            }
        }
        
        AuditLog::log('Updated voucher', 'Vouchers', $voucher->name);
        return redirect()->route('superadmin.voucher.index')->with('success', 'Voucher updated.');
    }

    /**
     * Get voucher details for modal
     */
    public function details(Voucher $voucher)
    {
        return response()->json([
            'success' => true,
            'voucher' => $voucher,
        ]);
    }

    /**
     * Get existing codes for a voucher (for edit mode)
     */
    public function getCodes(Voucher $voucher)
    {
        $codes = $voucher->histories()
            ->whereIn('status', ['pending', 'available'])
            ->pluck('code');
            
        return response()->json([
            'success' => true, 
            'codes' => $codes
        ]);
    }

    /**
     * Display coupons page
     */
    public function coupons(Request $request, Voucher $voucher)
    {
        $perPage = $request->get('per_page', 10);
        
        $coupons = VoucherHistory::where('tenant_id', getSuperAdminTenantId())
            ->where('voucher_id', $voucher->id)
            ->with(['assignedUser', 'usedUser'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
        
        $stats = [
            'total' => $coupons->count(),
            'available' => $coupons->where('status', 'available')->count(),
            'used' => $coupons->where('status', 'used')->count(),
            'expired' => $coupons->where('status', 'expired')->count(),
            'total_points' => $coupons->where('status', 'used')->sum('deduct_point'),
        ];
        
        return view('superadmin.voucher.coupons', compact('voucher', 'coupons', 'stats'));
    }

    /**
     * Approve redemption request
     */
    public function approve(Redemption $redemption)
    {
        $redemption->update(['status' => 'fulfilled']);
        AuditLog::log('Approved redemption', 'Vouchers', 'TXN-' . $redemption->id);
        return back()->with('success', 'Redemption approved.');
    }

    /**
     * Reject redemption request
     */
    public function reject(Redemption $redemption)
    {
        $redemption->update(['status' => 'rejected']);
        AuditLog::log('Rejected redemption', 'Rewards', 'TXN-' . $redemption->id);
        return back()->with('success', 'Redemption rejected.');
    }

    /**
     * Validate request data
     */
    private function validated(Request $request): array
    {
        $rules = [
            'name' => 'required|string|max:191',
            'category' => 'required|string|in:Mobile Recharge,Bill Payment,Voucher',
            'partner' => 'nullable|string|max:100',
            'points' => 'nullable|integer|min:0',
            'coin_cost' => 'nullable|integer|min:0',
            'no_of_coupon' => 'nullable|integer|min:0',
            'expire_duration' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive,out_of_stock',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ];

        // Add manual codes validation for external brands
        if (!$this->isSelfVoucher($request->input('partner'))) {
            if (!$request->route('voucher')) { // Only for store (not update)
                $rules['manual_codes'] = 'required|array|min:1';
            }
            $rules['manual_codes.*'] = 'nullable|string|max:100';
            $rules['new_manual_codes.*'] = 'nullable|string|max:100';
        }

        return $request->validate($rules);
    }

    /**
     * Change voucher status
     */
    public function changeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:vouchers,id',
            'status' => 'required|in:active,inactive,out_of_stock',
        ]);

        $voucher = Voucher::findOrFail($request->id);
        $voucher->update(['status' => $request->status]);
        AuditLog::log('Updated voucher status', 'Vouchers', $voucher->name);

        return response()->json([
            'success' => true,
            'message' => 'Voucher status updated successfully.',
        ]);
    }

    /**
     * Add more coupons to existing voucher
     */
    public function addCoupons(Request $request, Voucher $voucher)
    {
        $request->validate([
            'no_of_coupon' => 'required|integer|min:1|max:1000',
            'expire_duration' => 'nullable|integer|min:0',
        ]);
        
        $count = (int)$request->no_of_coupon;
        $expireDuration = $request->expire_duration ? (int)$request->expire_duration : null;
        
        $this->generateCoupons($voucher, $count, $expireDuration);
        
        // Update voucher's coupon count
        $voucher->update([
            'no_of_coupon' => ($voucher->no_of_coupon ?? 0) + $count,
            'expire_duration' => $expireDuration ?? 0,
        ]);
        
        AuditLog::log('Added coupons', 'Vouchers', $voucher->name . ' (' . $count . ' coupons)');
        
        return redirect()
            ->route('superadmin.voucher.coupons', $voucher)
            ->with('success', $count . ' coupons generated successfully.');
    }

    /**
     * Delete voucher (soft delete)
     */
    public function destroy(Voucher $voucher) 
    {
        $voucherName = $voucher->name;
        $voucher->delete(); 
        
        AuditLog::log('Deleted voucher', 'Vouchers', $voucherName);
        
        return response()->json([
            'success' => true,
            'message' => 'Voucher deleted successfully (Moved to trash).',
        ]);
    }

    /**
     * Check if voucher is self-generated (auto codes)
     */
    private function isSelfVoucher(?string $partner): bool
    {
        $selfValues = ['self', 'self-voucher', ''];
        return empty($partner) || in_array(strtolower(trim($partner)), $selfValues);
    }

    /**
     * Auto-generate coupon codes
     */
    private function generateCoupons(Voucher $voucher, int $count, ?int $expireDuration = null): void
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $voucher->name), 0, 2));
        $prefix = $prefix ?: 'RW';
        
        $generated = 0;
        $maxAttempts = $count * 20;
        $attempts = 0;
        
        $duration = $expireDuration ?? $voucher->expire_duration;
        
        while ($generated < $count && $attempts < $maxAttempts) {
            $code = $prefix . random_int(1000, 9999);
            
            // Ensure unique code
            if (!VoucherHistory::where('code', $code)->exists()) {
                VoucherHistory::create([
                    'tenant_id' => $voucher->tenant_id ?? getSuperAdminTenantId(),
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

    /**
     * Create manual coupon codes with uniqueness check
     */
    private function createManualCodes(Voucher $voucher, array $codes): void
    {
        foreach ($codes as $code) {
            $code = trim($code);
            if (empty($code)) continue;

            // Skip if code already exists globally
            if (VoucherHistory::where('code', $code)->exists()) {
                continue;
            }

            VoucherHistory::create([
                'tenant_id'      => $voucher->tenant_id,
                'voucher_id'     => $voucher->id,
                'voucher_type'   => 'voucher',
                'code'           => $code,
                'expire_duration'=> $voucher->expire_duration,
                'status'         => 'pending',
                'deduct_point'   => $voucher->points ?? $voucher->coin_cost ?? 0,
            ]);
        }
    }

    /**
     * Add manual coupons for external brand vouchers (Amazon, Flipkart, etc.)
     */
    public function addManualCoupons(Request $request, Voucher $voucher)
    {
        $request->validate([
            'manual_codes' => 'required|array|min:1',
            'manual_codes.*' => 'required|string|max:100',
            'expire_duration' => 'nullable|integer|min:0',
        ]);

        $codes = $request->input('manual_codes', []);
        $expireDuration = $request->expire_duration ? (int)$request->expire_duration : null;
        
        $addedCount = 0;
        $skippedCodes = [];
        $duplicateInForm = [];
        $seenCodes = [];

        foreach ($codes as $code) {
            $code = trim($code);
            if (empty($code)) continue;

            // Check for duplicates within the form submission
            if (in_array($code, $seenCodes)) {
                $duplicateInForm[] = $code;
                continue;
            }
            $seenCodes[] = $code;

            // Check if code already exists in database
            if (VoucherHistory::where('code', $code)->exists()) {
                $skippedCodes[] = $code;
                continue;
            }

            // Create the coupon
            VoucherHistory::create([
                'tenant_id' => $voucher->tenant_id ?? getSuperAdminTenantId(),
                'voucher_id' => $voucher->id,
                'voucher_type' => 'voucher',
                'code' => $code,
                'expire_duration' => $expireDuration ?? $voucher->expire_duration,
                'status' => 'pending',
                'deduct_point' => $voucher->points ?? $voucher->coin_cost ?? 0,
            ]);
            
            $addedCount++;
        }

        // Build success message
        $message = "{$addedCount} coupon(s) added successfully.";
        
        if (!empty($skippedCodes)) {
            $message .= " Skipped (already exist): " . implode(', ', $skippedCodes) . ".";
        }
        
        if (!empty($duplicateInForm)) {
            $message .= " Skipped (duplicates in form): " . implode(', ', array_unique($duplicateInForm)) . ".";
        }

        AuditLog::log(
            'Added manual coupons',
            'Vouchers',
            $voucher->name . ' (' . $addedCount . ' coupons added)'
        );

        return redirect()
            ->route('superadmin.voucher.coupons', $voucher)
            ->with('success', $message);
    }

    public function checkCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:100',
        ]);

        $exists = VoucherHistory::where('code', trim($request->code))->exists();

        return response()->json([
            'exists' => $exists,
            'code' => $request->code,
        ]);
    }
}