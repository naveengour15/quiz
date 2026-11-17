<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
         $perPage = $request->get('per_page', 10);
        $logs = AuditLog::query()
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('admin_name', 'like', "%{$request->search}%")
                    ->orWhere('action', 'like', "%{$request->search}%")
                    ->orWhere('record', 'like', "%{$request->search}%");
            }))
            ->when($request->module, fn ($q) => $q->where('module', $request->module))
            ->when($request->date, fn ($q) => $q->whereDate('created_at', $request->date))
            ->latest()
            ->paginate($perPage)->withQueryString();

        return view('superadmin.audit.index', compact('logs'));
    }
}
