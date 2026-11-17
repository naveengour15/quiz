<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ModerationReport;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function index(Request $request)
    {
        $queue = ModerationReport::where('status', 'pending')->latest()->get();
        $active = $request->item
            ? ModerationReport::findOrFail($request->item)
            : $queue->first();

        return view('superadmin.moderation.index', compact('queue', 'active'));
    }

    public function decide(Request $request, ModerationReport $report)
    {
        $data = $request->validate([
            'decision' => 'required|in:approve,edit_republish,remove,dismiss',
            'notify_creator' => 'nullable|boolean',
            'corrected_answer' => 'nullable|string',
            'internal_reason' => 'required|string',
        ]);
        $data['status'] = 'resolved';

        $report->update($data);
        AuditLog::log('Moderation decision: ' . $data['decision'], 'Content', $report->title);

        return redirect()->route('superadmin.moderation.index')->with('success', 'Decision submitted.');
    }
}
