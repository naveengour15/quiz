<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppUpdation extends Model
{
    use HasFactory;

    protected $table = 'app_updations';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'subject',
        'message',
        'recipient_type',
        'sender_id',
        'sender_name',
        'status',
    ];

    protected $casts = [
        'user_id' => 'array',
        'tenant_id' => 'array',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function histories()
    {
        return $this->hasMany(
            AppUpdationHistory::class,
            'app_updation_id'
        );
    }
}
