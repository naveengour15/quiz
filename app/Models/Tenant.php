<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Tenant extends Authenticatable
{
     protected $fillable = [
        'user_id',
        'name',
        'slug',
        'domain',
        'sub_domain',
        'prefix',
        'email',
        'phone',
        'logo',
        'pan_number',
        'gstin',
        'is_gst',
        'address',
        'latitude',
        'longitude',
        'address1',
        'landmark',
        'pincode',
        'city',
        'state',
        'website',
        'qr_code',
        'status',
        'referral_code',
        'user_referral_limit',
        'system_type',
        'online_status',
        'payment_option',
        'store_status',
        'css_theme',
        'theme',
        'qr_theme',
        'menu_theme',
        'website_color_theme',
        'favicon',
        'dark_logo',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'open_time',
        'close_time',
        'off_day',
        'paid',
        'half_day_time',
        'full_day_time',
        'max_paid_leave_apply',
        'updated_by_id',
        'updated_by_name',
        'updated_by_role',
        'gateway_charges',
        'gateway_charges_status',
        'quiz_max_player_limit',
        'quiz_max_question_limit',
        'quiz_limit',
        'quiz_daily_play_limit',
    ];

    protected $casts = [
        'is_gst'     => 'boolean',
        'css_theme'  => 'array',
    ];

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
        // No-op: tenants table doesn't have remember_token column
    }

    public function getRememberTokenName()
    {
        return '';
    }
}
