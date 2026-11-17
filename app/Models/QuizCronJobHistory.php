<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizCronJobHistory extends Model
{
    use HasFactory;

    protected $table = 'quiz_cron_job_history';

    protected $fillable = [
        'tenant_id',
        'quiz_id',
        'scheduled_at',
        'cron_type',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
