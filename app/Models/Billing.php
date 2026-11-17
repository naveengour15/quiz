<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Billing extends Model
{
    use HasFactory;

    protected $table = 'billings';

    protected $fillable = [
        'tenant_id',
        'contact',
        'payment_table_id',
        'plan_id',
        'plan_name',
        'plan_for',
        'plan_duration',
        'plan_amount',
        'offer_amount',
        'addon_service_amount',
        'addon_service',
        'free_notification',
        'payable_amount',
        'tax',
        'plan_start_date',
        'plan_end_date',
        'invoice_path',
        'payment_method',
        'payment_status',
        'pay_to',
        'sms',
        'sms_status',
        'email',
        'email_status',
        'whatsapp',
        'whatsapp_status',
    ];

    protected $casts = [
        'addon_service' => 'array',
        'plan_amount' => 'decimal:2',
        'offer_amount' => 'decimal:2',
        'addon_service_amount' => 'decimal:2',
        'payable_amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'plan_start_date' => 'date',
        'plan_end_date' => 'date',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
    
    public function payment()
    {
        return $this->belongsTo(PlanPayment::class, 'payment_table_id', 'id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

}
