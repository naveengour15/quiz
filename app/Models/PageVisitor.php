<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageVisitor extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'page_name',
        'page_url',
        'route_name',
        'ip_address',
        'device_type',
        'browser',
        'platform',
        'user_agent',
        'visit_date',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Associated logged-in user (if any)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope for today's visits
     */
    public function scopeToday($query)
    {
        return $query->where('visit_date', now()->toDateString());
    }

    /**
     * Scope for active live visitors (default last 15 minutes)
     */
    public function scopeLive($query, int $minutes = 15)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope for filtering date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        if ($from && $to) {
            return $query->whereBetween('visit_date', [$from, $to]);
        } elseif ($from) {
            return $query->where('visit_date', '>=', $from);
        } elseif ($to) {
            return $query->where('visit_date', '<=', $to);
        }
        return $query;
    }
}

