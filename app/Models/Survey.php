<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Survey extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;


    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'reward_type',
        'point',
        'quiz',
        'published',
        'published_at',
        'status',
    ];

      protected $attributes = [
        'status' => 'active',
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

    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class);
    }

    public function rewards()
    {
        return $this->hasMany(SurveyReward::class);
    }

    public function answers()
    {
        return $this->hasMany(SurveyAnswer::class);
    }

   

}