<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = Transaction::with('user')
            ->when($request->search, fn ($q) => $q->where('txn_id', 'like', "%{$request->search}%"))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->date, fn ($q) => $q->whereDate('created_at', $request->date))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('superadmin.transactions.index', compact('transactions'));
    }

    public function refund(Transaction $transaction)
    {
        $transaction->update(['status' => 'refunded']);
        AuditLog::log('Refunded transaction', 'Billing', $transaction->txn_id);

        return back()->with('success', 'Transaction refunded.');
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:transactions,id',
            'status' => 'required|in:success,failed,refunded',
        ]);

        $transaction = Transaction::findOrFail($request->id);

        $transaction->update([
            'status' => $request->status
        ]);

        AuditLog::log(
            'Updated transaction status',
            'Billing',
            $transaction->txn_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Transaction status updated.'
        ]);
    }
    
}
