<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyReward extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'survey_id',
        'user_id',
        'reward_type',
        'point',
        'quiz',
        'status',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}