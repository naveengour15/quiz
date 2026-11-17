<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanBilling extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'payment_id',
        'plan_id',
        'plan_name',
        'validity_days',
        'extra_days',
        'plan_duration',
        'plan_amount',
        'offer_amount',
        'addon_service_amount',
        'addon_service',
        'tax',
        'payable_amount',
        'sms',
        'sms_status',
        'whatsapp',
        'whatsapp_status',
        'email',
        'email_status',
        'plan_start_date',
        'plan_end_date',
        'payment_method',
        'transaction_id',
        'billing_date',
        'expiry_date',
        'payment_status',
        'status',
        'remarks',
    ];

    protected $casts = [
        'addon_service' => 'array',

        'plan_amount' => 'decimal:2',
        'offer_amount' => 'decimal:2',
        'addon_service_amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'payable_amount' => 'decimal:2',

        'plan_start_date' => 'date',
        'plan_end_date' => 'date',
        'billing_date' => 'date',
        'expiry_date' => 'date',
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
     * Payment Relationship
     */
    public function payment()
    {
        return $this->belongsTo(PlanPayment::class);
    }
}
