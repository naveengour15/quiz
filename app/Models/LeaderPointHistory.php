<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderPointHistory extends Model
{
    use HasFactory;

    protected $table = 'leader_point_history';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'quiz_id',
        'old_point',
        'point',
        'new_point',
        'type',
    ];

    protected $casts = [
        'point' => 'decimal:2',
        'old_point' => 'decimal:2',
        'new_point' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }
}