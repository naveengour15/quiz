<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EGiftFrom extends Model
{
    use HasFactory;

    protected $table = 'e_gift_froms';

    protected $fillable = [
        'tenant_id',
        'name',
        'status',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
    ];
}