<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'quiz_id',
        'ads_campaign_id',
        'state',
        'city',
        'area',
        'age_range',
        'gender',
        'hobby',
        'status',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city');
    }
    public function adCampaign()
    {
        return $this->belongsTo(Ad::class, 'ads_campaign_id');
    }

}