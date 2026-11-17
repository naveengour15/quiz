<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizNotification extends Model
{
    use SoftDeletes;

    protected $table = 'quiz_notifications';
    protected $fillable = [
        'quiz_id',
        'user_name',
        'quiz_name',
        'quiz_schedule_time',
        'user_id',
        'notificationactiv_date_time',
        'status',
        'msg',
        'sent_date_time',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
