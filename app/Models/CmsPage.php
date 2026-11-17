<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'title', 'slug', 'content', 'meta_title', 'meta_description', 'status',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $model->tenant_id = 1;
            }
        });
    }
}
