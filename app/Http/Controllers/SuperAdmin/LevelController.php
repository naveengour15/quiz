<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Level;
use Illuminate\Http\Request;

class LevelController extends Controller
{

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $levels = Level::where('tenant_id', getSuperAdminTenantId())
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()->paginate($perPage)->withQueryString();

        $editing = $request->edit? Level::findOrFail($request->edit): null;

        return view('superadmin.levels.index', compact('levels', 'editing'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
             'number_quiz'  => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $data['tenant_id'] = getSuperAdminTenantId();

        Level::create($data);
        AuditLog::log('Created Level', 'Content', $data['name']);

        return redirect()->route('superadmin.levels.index')->with('success', 'Level created successfully.');
    }

    public function update(Request $request, Level $level)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
             'number_quiz'  => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);
         $data['tenant_id'] = getSuperAdminTenantId();

        $level->update($data);
        AuditLog::log('Updated Level', 'Content', $level->name);

        return redirect()->route('superadmin.levels.index')->with('success', 'Level updated successfully.');
    }

    public function destroy(Level $level)
    {
        AuditLog::log('Deleted Level', 'Content', $level->name);
        $level->delete();

        return back()->with('success', 'Level deleted successfully.');
    }

    public function updateStatus(Level $level)
    {
        $level->status = $level->status == 'active' ? 'inactive' : 'active';
        $level->save();

        AuditLog::log(
            'Updated Level Status',
            'Content',
            $level->name . ' (' . $level->status . ')'
        );

        return response()->json([
            'success' => true,
            'status'  => $level->status
        ]);
    }
}
