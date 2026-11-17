<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    protected $fillable = [
        'tenant_id',
        'number_quiz',
        'name',
        'status',
    ];
}