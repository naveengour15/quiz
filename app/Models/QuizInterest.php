<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizInterest extends Model
{
    use HasFactory;

    protected $table = 'quiz_interest';

    protected $fillable = [
        'name',
        'status',
    ];
}