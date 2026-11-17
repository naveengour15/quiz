<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantMessageSetting extends Model
{
   protected $fillable = [
        'tenant_id',
        'whatsapp',
        'sms',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
