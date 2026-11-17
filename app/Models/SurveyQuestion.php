<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyQuestion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'survey_id',
        'question',
        'yes',
        'no',
        'correct_ans',
        'status',
    ];

     protected $attributes = [
        'yes'         => 'Yes',
        'no'          => 'No',
        'correct_ans' => 'yes',
        'status'      => 'active',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function answers()
    {
        return $this->hasMany(SurveyAnswer::class, 'question_id');
    }
}