<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppUpdationHistory extends Model
{
    use HasFactory;

    protected $table = 'app_updation_history';

    protected $fillable = [
        'tenant_id',
        'app_updation_id',
        'user_id',
        'status',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function appUpdation()
    {
        return $this->belongsTo(
            AppUpdation::class,
            'app_updation_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function tenant()
    {
        return $this->belongsTo(
            Tenant::class,
            'tenant_id'
        );
    }
}
