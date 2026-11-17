<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
         $perPage = $request->get('per_page', 10);
        $coupons = Coupon::latest()->paginate($perPage)->withQueryString();
        $editing = $request->edit ? Coupon::findOrFail($request->edit) : null;

        return view('superadmin.coupons.index', compact('coupons', 'editing'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Coupon::create($data);
        AuditLog::log('Created coupon', 'Billing', $data['code']);

        return redirect()->route('superadmin.coupons.index')->with('success', 'Coupon saved.');
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $this->validated($request);
        $coupon->update($data);
        AuditLog::log('Updated coupon', 'Billing', $coupon->code);

        return redirect()->route('superadmin.coupons.index')->with('success', 'Coupon updated.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'code' => 'required|string|max:50',
           // 'discount_type' => 'required|in:percentage,flat',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'usage_limit_total' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'required|integer|min:1',
            'applicable_plans.*' => 'nullable|string',
            'expiry_date' => 'required|date',
            // 'status' => 'required|in:active,paused,expired',
            'status' => 'required|in:active,inactive',
        ]);
        $data['applicable_plans'] = $request->input('applicable_plans', []);

        return $data;
    }


    public function changeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:coupons,id',
            'status' => 'required|in:active,inactive',
        ]);

        $coupon = Coupon::findOrFail($request->id);

        $coupon->update([
            'status' => $request->status,
        ]);

        AuditLog::log(
            'Updated coupon status',
            'Billing',
            $coupon->code
        );

        return response()->json([
            'success' => true,
            'message' => 'Coupon status updated successfully.'
        ]);
    }

}
