<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\State;

class WebEnquiry extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'web_enquiries';

    protected $fillable = [
        'tenant_id',
        'phone',
        'otp',
        'otp_verify',
        'current_step',
        'business_name',
        'email',
        'business_type',
        'state',
        'city',
        'address',
        'inquiry_status',
        'updated_by_id',
        'updated_by_name',
        'updated_by_role_id',
    ];


    public function stateRelation()
    {
        return $this->belongsTo(State::class, 'state', 'id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}