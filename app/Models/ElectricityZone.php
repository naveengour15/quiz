<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ElectricityZone extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'electricity_zones';

    protected $fillable = [
        'state_id',
        'zone',
        'zone_code',
        'status',
    ];

    /**
     * Get the state associated with the electricity zone.
     */
    public function state()
    {
        return $this->belongsTo(State::class);
    }
}