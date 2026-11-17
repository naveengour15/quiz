<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedeemRequest extends Model
{
    use SoftDeletes;

    protected $table = 'redeem_requests';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'request_type',
        'zone_id',
        'company',
        'post_prepaid',
        'mobile',
        'redeem_amount',
        'consumer_number',
        'status',
        'user_note',
        'approver_note',
        'redeem_msg_status',
    ];

    protected $casts = [
        'tenant_id'     => 'integer',
        'user_id'       => 'integer',
        'zone_id'       => 'integer',
        'redeem_amount' => 'double',
    ];

     const TYPE_RECHARGE    = 'recharge';
    const TYPE_ELECTRICITY = 'electricity_bill';

    const STATUS_PENDING   = 'pending';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

     // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(State::class, 'zone_id');
    }

     // ✅ NEW: Electricity Zone ke liye alag relationship
    public function electricityZone(): BelongsTo
    {
        return $this->belongsTo(ElectricityZone::class, 'zone_id', 'id');
    }
}