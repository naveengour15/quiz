<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    protected $table = 'device_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'device_id',
        'brand',
        'manufacturer',
        'model',
        'device_name',
        'os_name',
        'os_version',
        'api_level',
        'security_patch',
        'app_version',
        'app_build',
        'package_name',
        'locale',
        'timezone',
        'is_physical_device',
        'extra',
        'is_active',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'api_level' => 'integer',
            'is_physical_device' => 'boolean',
            'extra' => 'array',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Device token belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}