<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InboxNotification extends Model
{
    use SoftDeletes;

    protected $table = 'notifications';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'audience',
        'type',
        'title',
        'body',
        'image_url',
        'deep_link',
        'action',
        'data',
        'related_type',
        'related_id',
        'topic',
        'status',
        'published_at',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'data' => 'array',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
