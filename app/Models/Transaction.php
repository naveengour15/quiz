<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'txn_id', 'user_id', 'type', 'amount', 'method', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
