<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'channels', 'segment', 'schedule_type', 'scheduled_at',
        'title', 'message', 'deep_link', 'sent_at', 'open_rate', 'status',
    ];

    protected $casts = [
        'channels' => 'array',
    ];
}
