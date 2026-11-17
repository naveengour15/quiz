<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessingFee extends Model
{
    use HasFactory;

    protected $table = 'processing_fees';

    protected $fillable = [
        'tenant_id',
        'recharge',
        'electricity_bill',
        'voucher',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'recharge' => 'decimal:2',
        'electricity_bill' => 'decimal:2',
        'voucher' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
