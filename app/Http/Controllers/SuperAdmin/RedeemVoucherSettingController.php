<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\RedeemVoucherSetting;
use Illuminate\Http\Request;

class RedeemVoucherSettingController extends Controller
{
    /**
     * Display the redeem voucher settings page.
     */
    public function index()
    {
        // Saari settings fetch karo (tenant_id = 1 ke liye)
        $settings = RedeemVoucherSetting::where('tenant_id', 1)->orderBy('id', 'asc')->get();
        
        return view('superadmin.quiz-settings.redeem_voucher_settings', compact('settings'));
    }

    /**
     * Update the redeem voucher settings.
     */
    public function update(Request $request)
    {
        // Array validation: Har setting ke liye type, limit_value, aur status check karega
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.type' => 'required|string',
            'settings.*.limit_value' => 'required|integer|min:0',
            'settings.*.status' => 'required|in:active,inactive',
        ]);

        // Loop chalakar har setting ko updateOrCreate karo
        foreach ($validated['settings'] as $data) {
            RedeemVoucherSetting::updateOrCreate(
                [
                    'tenant_id' => 1, // Ya auth()->user()->tenant_id agar dynamic hai
                    'type' => $data['type']
                ],
                [
                    'limit_value' => $data['limit_value'],
                    'status' => $data['status'],
                ]
            );
        }

        // Audit Log Entry
        AuditLog::log('Updated Redeem Voucher Settings', 'Settings');

        return back()->with('success', 'Redeem voucher settings updated successfully.');
    }
}