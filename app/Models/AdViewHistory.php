<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdViewHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'ad_id',
       'type',
        'banner_id',
        'quiz_id',
        'viewer_id',
        'session_id',
        'ip_address',
        'device',
        'browser',
        'os',
        'country',
        'state',
        'city',
        'referrer',
        'watch_time',
        'is_unique',
        'viewed_at',
        'page_type'
    ];

    protected $casts = [
        'is_unique' => 'boolean',
        'viewed_at' => 'datetime',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function viewer()
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }
}