<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizUserFeedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'quiz_user_feedback';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'subject',
        'message',

        'review_note',
        'reviewed_by',
        'reviewed_at',

        'reply_message',
        'replied_by',
        'replied_at',

        'close_note',
        'closed_by',
        'closed_at',

        'status',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'replied_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by');
    }

    public function repliedBy()
    {
        return $this->belongsTo(AdminUser::class, 'replied_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(AdminUser::class, 'closed_by');
    }
}