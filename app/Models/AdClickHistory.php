<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdClickHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'ad_id',
        'quiz_id',
        'user_id',
        'type',
        'banner_id',
        'session_id',
        'ip_address',
        'device',
        'browser',
        'os',
        'country',
        'state',
        'city',
        'destination_url',
        'clicked_at',
        'page_type'
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}