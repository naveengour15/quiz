<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'quiz_id',
        'campaign_name',
        'title',
        'description',
        'ad_type',
        'media',
        'cta',
        'destination_url',
        'budget_type',
        'budget',
        'start_date',
        'end_date',
        'country',
        'state',
        'city',
        'age_from',
        'age_to',
        'gender',
        'language',
        'interests',
        'status',
        'is_featured',
        'priority',
         'hobby'
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_featured' => 'boolean',
        'interests' => 'array',
        'hobby' => 'array',
        'quiz_id' => 'array',
    ];

   
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    } 

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function analytics()
    {
        return $this->hasOne(AdAnalytic::class);
    }

    public function views()
    {
        return $this->hasMany(AdViewHistory::class);
    }

    public function clicks()
    {
        return $this->hasMany(AdClickHistory::class);
    }

   public function stateData()
    {
        return $this->belongsTo(State::class, 'state');
    }

    public function cityData()
    {
        return $this->belongsTo(City::class, 'city');
    }

    public function mediaFile()
    {
        return $this->hasOne(AdMedia::class);
    }

    

}