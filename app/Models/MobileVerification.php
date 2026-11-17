<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'mobile_number',
        'otp',
        'token',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}