<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public const MODULES = [
        'Users — View', 'Users — Edit', 'Users — Suspend/Ban', 'Content — Moderate',
        'Rewards — Approve Redemption', 'Billing — Refund', 'Reports — Export', 'Roles — Manage',
    ];

    public function index()
    {
        $roles = Role::latest()->get();
        $modules = self::MODULES;

        return view('superadmin.roles.index', compact('roles', 'modules'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'base_template' => 'nullable|string',
            'description' => 'required|string',
            'requires_2fa' => 'required|boolean',
        ]);
        $data['permissions'] = $request->input('permissions', []);

        Role::create($data);
        AuditLog::log('Created role', 'Roles', $data['name']);

        return redirect()->route('superadmin.roles.index')->with('success', 'Role created.');
    }

    public function updatePermissions(Request $request)
    {
        foreach ($request->input('permissions', []) as $roleId => $modules) {
            Role::whereKey($roleId)->update(['permissions' => array_values($modules)]);
        }
        AuditLog::log('Updated permission matrix', 'Roles');

        return back()->with('success', 'Permission matrix saved.');
    }
}
