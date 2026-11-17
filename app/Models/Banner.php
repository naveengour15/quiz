<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
  use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'banner_file',
        'url',
        'type',
        'status',
    ];

    protected $casts = [
        'type' => 'array',
    ];

    protected $appends = [
        'banner_url',
    ];

    protected static function booted()
    {
        static::saved(function ($banner) {
            self::clearBannerCaches($banner);
        });

        static::deleted(function ($banner) {
            self::clearBannerCaches($banner);
        });
    }

    public static function clearBannerCaches($banner = null)
    {
        try {
            \Illuminate\Support\Facades\Cache::forget('fsr_active_banners_waiting');
            \Illuminate\Support\Facades\Cache::forget('fsr_active_banners');
            \Illuminate\Support\Facades\Cache::forget('fsr_banners_1');
            if ($banner && !empty($banner->tenant_id)) {
                \Illuminate\Support\Facades\Cache::forget("fsr_banners_{$banner->tenant_id}");
            }
        } catch (\Throwable $e) {}
    }

    public function getBannerUrlAttribute()
    {
        if (!$this->banner_file) {
            return '';
        }
        if (str_starts_with($this->banner_file, 'http://') || str_starts_with($this->banner_file, 'https://')) {
            return $this->banner_file;
        }
        try {
            return Storage::disk('s3')->url($this->banner_file);
        } catch (\Throwable $e) {
            return asset('storage/' . $this->banner_file);
        }
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

      /* Banner View History  */
    public function viewHistories()
    {
        return $this->hasMany( AdViewHistory::class,'banner_id');
    }

    /*Banner Click History */
    public function clickHistories()
    {
        return $this->hasMany(AdClickHistory::class,'banner_id');
    }

}
