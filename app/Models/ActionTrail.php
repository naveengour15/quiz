<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionTrail extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'quiz_id',
        'user_id',
        'survey_id',
        'table_name',
        'reference_id',
        'action',
        'updated_by',
        'updated_by_role_id',
        'old_details',
        'details',
        'status',
    ];

    protected $casts = [
        'old_details' => 'array',
        'details'     => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'updated_by_role_id');
    }
}