<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplateSetting extends Model
{
    use HasFactory;

    protected $table = 'whats_app_template_settings';

    protected $fillable = [
        'tenant_id',
        'template_name',
        'template_code',
        'language_code',
        'phone_no',
        'data',
        'description',
        'status',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}