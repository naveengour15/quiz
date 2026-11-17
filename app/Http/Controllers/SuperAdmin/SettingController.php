<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Models\AdminUser;
use App\Models\TenantMessageSetting;
use App\Models\ProcessingFee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index()
    {
        $admin = Auth::guard('superadmin')->user();
        $tenantId = $admin->tenant_id ?? 1;

        $settings = Setting::pluck('value', 'key');
        $processingFee = ProcessingFee::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'recharge' => 0,
                'electricity_bill' => 0,
                'voucher' => 0,
            ]
        );

        $quizzes = \App\Models\Quiz::whereHas('questions')->latest('id')->take(30)->get(['id', 'title', 'scheduled_at', 'visibility']);

        return view('superadmin.settings.index', compact('settings', 'processingFee', 'quizzes'));
    }

    public function update(Request $request)
    {
        foreach ($request->except('_token') as $key => $value) {
            Setting::set($key, $value);
        }
        AuditLog::log('Updated settings', 'Settings');

        return back()->with('success', 'Settings saved.');
    }


    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => ['required'],
            'new_password' => ['required', 'min:6'],
            'new_password_confirmation' => ['required', 'same:new_password'],
        ], [
            'old_password.required' => 'Old password is required.',

            'new_password.required' => 'New password is required.',
            'new_password.min' => 'Password must be at least 6 characters.',

            'new_password_confirmation.required' => 'Confirm password is required.',
            'new_password_confirmation.same' => 'Confirm password does not match.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('active_tab', 'password');
        }

        // SuperAdmin ke liye superadmin guard use hoga
        $admin = Auth::guard('superadmin')->user();

        if (!$admin) {
            return back()
                ->with('active_tab', 'password')
                ->with('error', 'Super Admin not found.');
        }

        // Old password check
        if (!Hash::check($request->old_password, $admin->password)) {
            return back()
                ->with('active_tab', 'password')
                ->withErrors([
                    'old_password' => 'Old password is incorrect.'
                ])
                ->withInput();
        }

        // Current session ID
        $sessionId = $request->session()->getId();
        // dd($sessionId);die;

        // New password save
        $admin->password = Hash::make($request->new_password);
        $admin->save();

        // Audit log
        AuditLog::log(
            'Changed super admin password',
            'Settings'
        );

        // Current SuperAdmin session delete
        $session = DB::table('sessions')
            ->where('id', $sessionId)
            ->delete();

        // SuperAdmin logout
        Auth::guard('superadmin')->logout();

        // Laravel session destroy
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('superadmin.login')
            ->with(
                'success',
                'Password changed successfully. Please login again.'
            );
    }


    public function otpMessageSettings()
    {
        $admin = Auth::guard('superadmin')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin not found.'
            ], 401);
        }

        $tenantId = $admin->tenant_id;

        $settings = TenantMessageSetting::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'whatsapp' => 'active',
                'sms' => 'inactive',
            ]
        );

        return response()->json([
            'success' => true,
            'whatsapp' => $settings->whatsapp,
            'sms' => $settings->sms,
        ]);
    }


    public function updateOtpMessageSetting(Request $request)
    {
        $request->validate([
            'type' => ['required', 'in:whatsapp,sms'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $admin = Auth::guard('superadmin')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin not found.'
            ], 401);
        }

        $tenantId = $admin->tenant_id;

        $settings = TenantMessageSetting::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'whatsapp' => 'active',
                'sms' => 'inactive',
            ]
        );

        DB::transaction(function () use ($settings, $request) {
            $settings->{$request->type} = $request->status;
            $otherType = $request->type === 'whatsapp' ? 'sms' : 'whatsapp';
            $settings->{$otherType} = $request->status === 'active'
                ? 'inactive'
                : 'active';
            $settings->save();
        });

        AuditLog::log(
            'Updated OTP message ' . ucfirst($request->type) . ' status to ' . $request->status,
            'Settings'
        );

        return response()->json([
            'success' => true,
            'message' => ucfirst($request->type) . ' OTP setting updated successfully.',
            'status' => $settings->{$request->type},
            'whatsapp' => $settings->whatsapp,
            'sms' => $settings->sms,
        ]);
    }

    public function updateProcessingFee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recharge' => ['required', 'numeric', 'min:0', 'max:100'],
            'electricity_bill' => ['required', 'numeric', 'min:0', 'max:100'],
            'voucher' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'recharge.required' => 'Recharge processing fee is required.',
            'recharge.numeric' => 'Recharge processing fee must be a valid number.',
            'recharge.min' => 'Recharge processing fee cannot be negative.',
            'recharge.max' => 'Recharge processing fee cannot exceed 100%.',

            'electricity_bill.required' => 'Electricity bill processing fee is required.',
            'electricity_bill.numeric' => 'Electricity bill processing fee must be a valid number.',
            'electricity_bill.min' => 'Electricity bill processing fee cannot be negative.',
            'electricity_bill.max' => 'Electricity bill processing fee cannot exceed 100%.',

            'voucher.required' => 'Voucher processing fee is required.',
            'voucher.numeric' => 'Voucher processing fee must be a valid number.',
            'voucher.min' => 'Voucher processing fee cannot be negative.',
            'voucher.max' => 'Voucher processing fee cannot exceed 100%.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('active_tab', 'processing-fee');
        }

        $admin = Auth::guard('superadmin')->user();
        if (!$admin) {
            return back()
                ->with('error', 'Super Admin not found.')
                ->with('active_tab', 'processing-fee');
        }

        $tenantId = $admin->tenant_id ?? 1;

        $processingFee = ProcessingFee::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'recharge' => $request->recharge,
                'electricity_bill' => $request->electricity_bill,
                'voucher' => $request->voucher,
            ]
        );

        AuditLog::log(
            'Updated processing fee settings (Recharge: ' . $request->recharge . '%, Electricity: ' . $request->electricity_bill . '%, Voucher: ' . $request->voucher . '%)',
            'Settings'
        );

        return back()
            ->with('success', 'Processing fee settings updated successfully.')
            ->with('active_tab', 'processing-fee');
    }
}

