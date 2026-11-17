<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdAnalytic extends Model
{
    use HasFactory;

    protected $table = 'ad_analytics';

    protected $fillable = [        
        'tenant_id',
        'ad_id',
        'impressions',
        'views',
        'unique_views',
        'clicks',
        'favorites',
        'shares',
        'conversions',
        'ctr',
        'spent_amount',
    ];

    protected $casts = [
        'ctr' => 'decimal:2',
        'spent_amount' => 'decimal:2',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }
}