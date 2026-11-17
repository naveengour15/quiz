<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'base_template', 'description', 'requires_2fa', 'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
        'requires_2fa' => 'boolean',
    ];
}
