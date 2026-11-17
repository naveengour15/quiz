<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ModerationReport extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'item_type', 'item_id', 'title', 'reason', 'flagged_count',
        'ai_score', 'reporter_comment', 'status', 'decision', 'notify_creator',
        'corrected_answer', 'internal_reason',
    ];
}
