<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'contact',
        'user_id',
        'plan_id',
        'payment_type',
        'payment_amount',
        'payment_id',
        'signature',
        'status',
        'error_message',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
    ];

    /**
     * Tenant Relationship
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * User Relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Plan Relationship
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Billing Relationship
     */
    public function billing()
    {
        return $this->hasOne(PlanBilling::class);
    }
}
