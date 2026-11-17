<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'admin_users';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'mobile',
        'login_method',
        'password',
        'role',
        'role_id',
        'state',
        'city',
        'address',
        'profile_photo',
        'is_active',
        'can_login',
        'otp_enabled',
        'session_token',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'session_token',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'can_login'     => 'boolean',
        'otp_enabled'   => 'boolean',
        'last_login_at' => 'datetime',
        'password'      => 'hashed',
    ];

    /**
     * Tenant Relationship
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Role Relationship
     */
    public function roleData()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    
    public function isAllowedToLogin(): bool
    {
        return $this->is_active && $this->can_login;
    }

    /**
     * Check if user is Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->can_login;
    }
}