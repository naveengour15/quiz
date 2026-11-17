<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuditLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'admin_id', 'admin_name', 'action', 'module', 'record', 'ip_address',
    ];

    public static function log(string $action, ?string $module = null, ?string $record = null): void
    {
        try {
            $adminId = null;
            $adminName = 'System';

            if (auth('superadmin')->check()) {
                $user = auth('superadmin')->user();
                $adminName = $user->name ?? $user->email ?? 'Super Admin';
                if ($user && isset($user->id)) {
                    $adminId = User::where('id', $user->id)->exists() ? $user->id : null;
                }
            } elseif (auth('admin')->check()) {
                $user = auth('admin')->user();
                $adminName = $user->name ?? $user->store_name ?? 'Tenant Admin';
                if ($user && isset($user->id)) {
                    $adminId = User::where('id', $user->id)->exists() ? $user->id : null;
                }
            } elseif (auth()->check()) {
                $user = auth()->user();
                $adminName = $user->name ?? 'User';
                if ($user && isset($user->id)) {
                    $adminId = User::where('id', $user->id)->exists() ? $user->id : null;
                }
            }

            static::create([
                'admin_id' => $adminId,
                'admin_name' => $adminName,
                'action' => $action,
                'module' => $module,
                'record' => $record,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::error('AuditLog logging failed: ' . $e->getMessage());
        }
    }
}

