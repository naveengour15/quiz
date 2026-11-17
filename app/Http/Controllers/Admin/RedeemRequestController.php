<?php

namespace App\Http\Controllers\Admin;

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
            ->where('tenant_id', getTenantId())
            ->with(['user', 'tenant', 'zone'])
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

        return view('admin.redeem-request.index', compact('requests'));
    }

    public function show($id)
    {
        try {
            $request = RedeemRequest::with(['user', 'tenant', 'zone'])
                ->where('tenant_id', getTenantId())
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $request,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Redeem request not found.',
            ], 404);
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

            // Approve hone par wallet balance deduct karein
            if ($request->status === 'approved') {
                $user = $redeemRequest->user;
                
                // Balance check karein
                if ($user->wallet_balance < $redeemRequest->redeem_amount) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient wallet balance. User has only ₹' . number_format($user->wallet_balance, 2) . ' but requested ₹' . number_format($redeemRequest->redeem_amount, 2) . '.',
                    ], 422);
                }

                // Wallet balance deduct karein
                $user->wallet_balance -= $redeemRequest->redeem_amount;
                $user->save();

                // Wallet history mein entry karein
                WalletHistory::create([
                    'tenant_id'        => $redeemRequest->tenant_id,
                    'user_id'          => $user->id,
                    'amount'           => $redeemRequest->redeem_amount,
                    'type'             => 'debit',
                    'transaction_type' => 'redeem',
                    'reference_id'     => $redeemRequest->id,
                    'remarks'          => 'Redeem request approved - ' . ($request->note ?? 'No note'),
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