<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Auto-fills tenant_id on create and auto-scopes all queries
 * to the logged-in user's tenant. Add this trait to any model
 * that has a tenant_id column — no manual filtering needed.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $tenantId = getTenantId();
                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (Auth::guard('admin')->check() || request()->is('admin*')) {
                $tenantId = getTenantId();
                if ($tenantId) {
                    $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
                    return;
                }
            }

            if (Auth::guard('superadmin')->check()) {
                return;
            }

            $tenantId = getTenantId();
            if ($tenantId) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });
    }

}
