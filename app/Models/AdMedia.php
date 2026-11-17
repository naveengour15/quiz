<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdMedia extends Model
{
     use HasFactory;

    protected $table = 'ad_media';

    protected $fillable = [
        'tenant_id',
        'ad_id',
        'banner',
        'large_banner',
        'medium_banner',
        'full_screen',
        'video_portrait',
        'video_landscape',
    ];

    /**
     * Ad Relationship
     */
    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Tenant Relationship
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
