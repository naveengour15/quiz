<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\RedeemRequest;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\DB;


class RedeemRequestController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $requests = RedeemRequest::query()
            ->where('tenant_id', getSuperAdminTenantId())
            ->with(['user', 'tenant', 'zone', 'electricityZone'])
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($query) use ($search) {
                    $query->where('mobile', 'like', "%{$search}%")
                        ->orWhere('consumer_number', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('superadmin.redeem-request.index', compact('requests'));
    }

    // public function approveChangeStatus(Request $request)
    // {
    //     $request->validate([
    //         'id'             => 'required|exists:redeem_requests,id',
    //         'status' => 'required|in:approved,rejected,cancelled',
    //         'note'           => 'nullable|string|max:1000',
    //     ]);

    //     try {
    //         $redeemRequest = RedeemRequest::findOrFail($request->id);

    //         // Reject ke liye note zaroori hai
    //         if ($request->status === 'rejected' && empty(trim($request->note))) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Rejection note is required.',
    //             ], 422);
    //         }

    //         $updateData = [
    //             'status' => $request->status,
    //         ];

    //         if ($request->filled('note')) {
    //             $updateData['approver_note'] = $request->note;
    //         }

    //         $redeemRequest->update($updateData);

    //         AuditLog::log(
    //             'Updated redeem request status to ' . $request->status,
    //             'Redeem Request',
    //             'Request ID: ' . $redeemRequest->id . ' | Note: ' . ($request->note ?? 'None')
    //         );

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Redeem request status updated successfully.',
    //         ]);

    //     } catch (\Exception $e) {
    //         Log::error('Redeem request status update failed', [
    //             'request_id'     => $request->id,
    //             'status' => $request->status,
    //             'error'          => $e->getMessage(),
    //             'line'           => $e->getLine(),
    //             'file'           => $e->getFile(),
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong while updating the redeem request status.',
    //         ], 500);
    //     }
    // }

   public function show($id)
{
    try {
        // ✅ IMPORTANT: 'zone' aur 'electricityZone' dono ko with() mein load karein
        $request = \App\Models\RedeemRequest::with(['user', 'zone', 'electricityZone'])->findOrFail($id);

        $data = [
            'id'              => $request->id,
            'request_type'    => $request->request_type,
            'mobile'          => $request->mobile,
            'redeem_amount'   => $request->redeem_amount,
            'consumer_number' => $request->consumer_number,
            'company'         => $request->company,
            'status'          => $request->status,
            'user_note'       => $request->user_note,
            'approver_note'   => $request->approver_note,
            'created_at'      => $request->created_at,
            'updated_at'      => $request->updated_at,
            
            // User Data
            'user' => [
                'name'           => $request->user->name ?? '—',
                'email'          => $request->user->email ?? '—',
                'mobile'         => $request->user->mobile ?? '—',
                'wallet_balance' => $request->user->wallet_balance ?? 0,
            ],

            // ✅ Zone Data (Safe fallback ke saath)
            'zone' => $request->zone ? [
                'name' => $request->zone->name ?? '—',
            ] : null,

            // ✅ Electricity Zone Data (Apne actual column names ke hisaab se adjust karein)
            'electricityZone' => $request->electricityZone ? [
                'zone'      => $request->electricityZone->zone ?? $request->electricityZone->name ?? '—', 
                'zone_code' => $request->electricityZone->zone_code ?? 'N/A',
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'data'    => $data
        ]);

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Redeem Request Show Error: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch details. ' . $e->getMessage()
        ], 500);
    }
}






    public function approveChangeStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|exists:redeem_requests,id',
            'status' => 'required|in:approved,rejected,cancelled',
            'note'   => 'nullable|string|max:1000',
        ]);

        try {
            $redeemRequest = RedeemRequest::with('user')->findOrFail($request->id);

            // Reject ke liye note zaroori hai
            if ($request->status === 'rejected' && empty(trim($request->note))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rejection note is required.',
                ], 422);
            }

            // Agar already process ho chuka hai toh error
            if ($redeemRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This request has already been processed.',
                ], 422);
            }

            DB::beginTransaction();

            $updateData = [
                'status' => $request->status,
            ];

            if ($request->filled('note')) {
                $updateData['approver_note'] = $request->note;
            }

            // Reject ya Cancel hone par wallet balance refund karein (kyunki submit time par deduct ho chuka hai)
            if ($request->status === 'rejected' || $request->status === 'cancelled') {
                $user = $redeemRequest->user;
                
                // Wallet balance refund karein
                $user->wallet_balance += $redeemRequest->redeem_amount;
                $user->save();

                // Wallet history mein entry karein
                WalletHistory::create([
                    'tenant_id'        => $redeemRequest->tenant_id,
                    'user_id'          => $user->id,
                    'amount'           => $redeemRequest->redeem_amount,
                    'type'             => 'credit',
                    'title'            => 'Refund - ' . $redeemRequest->request_type,
                    'description'      => 'Points refunded for ' . $request->status . ' request',
                    'transaction_type' => 'refund',
                    'reference_id'     => $redeemRequest->id,
                    'remarks'          => 'Redeem request ' . $request->status . ' - ' . ($request->note ?? 'No note'),
                ]);
            }

            $redeemRequest->update($updateData);

            AuditLog::log(
                'Updated redeem request status to ' . $request->status,
                'Redeem Request',
                'Request ID: ' . $redeemRequest->id . ' | Amount: ₹' . $redeemRequest->redeem_amount . ' | Note: ' . ($request->note ?? 'None')
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Redeem request ' . $request->status . ' successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Redeem request status update failed', [
                'request_id' => $request->id,
                'status'     => $request->status,
                'error'      => $e->getMessage(),
                'line'       => $e->getLine(),
                'file'       => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating the redeem request status.',
            ], 500);
        }
    }

}