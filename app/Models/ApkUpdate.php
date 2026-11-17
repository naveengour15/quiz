<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ApkUpdate extends Model
{
    use BelongsToTenant;

    protected $table = 'apk_updates';

    protected $fillable = [
        'tenant_id',
        'version',
        'build_number',
        'release_title',
        'release_notes',
        'apk_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'build_number' => 'integer',
        ];
    }
}