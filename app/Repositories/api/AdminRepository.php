<?php

namespace App\Repositories\api;

use App\Interfaces\api\AdminRepositoryInterface;
use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminRepository implements AdminRepositoryInterface
{
    public function login(array $data): array
    {
        $admin = AdminUser::where('mobile', $data['mobile'])
            ->orWhere('email', $data['mobile'])
            ->first();

        if (! $admin || ! Hash::check($data['password'], $admin->password)) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        if (! $admin->can_login || ! $admin->is_active) {
            return ['success' => false, 'message' => 'Account is disabled, contact super admin'];
        }

        $sessionToken          = Str::random(60);
        $admin->session_token  = $sessionToken;
        $admin->last_login_at  = now();
        $admin->save();

        return [
            'success'      => true,
            'message'      => 'Login successful',
            'admin'        => $admin,
            'access_token' => $sessionToken,
        ];
    }

    public function list(int $tenantId): array
    {
        $data = AdminUser::where('tenant_id', $tenantId)->latest('id')->paginate(20);

        return ['success' => true, 'data' => $data];
    }

    public function create(array $data): array
    {
        $data['password']    = bcrypt($data['password'] ?? Str::random(10));
        $data['is_active']   = 1;
        $data['can_login']   = 1;
        $data['login_method']= 'password';

        $admin = AdminUser::create($data);

        return ['success' => true, 'message' => 'Admin created successfully', 'admin' => $admin];
    }

    public function rolesList(): array
    {
        return ['success' => true, 'data' => Role::where('status', 'active')->get()];
    }

    public function createRole(array $data): array
    {
        $role = Role::create($data);

        return ['success' => true, 'message' => 'Role created successfully', 'role' => $role];
    }

    public function tenantsList(): array
    {
        return ['success' => true, 'data' => Tenant::latest('id')->paginate(20)];
    }

    public function updateTenantStatus(int $tenantId, string $status): array
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return ['success' => false, 'message' => 'Tenant not found'];
        }

        $tenant->status = $status;
        $tenant->save();

        return ['success' => true, 'message' => 'Tenant status updated', 'tenant' => $tenant];
    }

    public function auditLogs(array $filters): array
    {
        $query = AuditLog::query();

        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        return ['success' => true, 'data' => $query->latest('id')->paginate(20)];
    }
}
