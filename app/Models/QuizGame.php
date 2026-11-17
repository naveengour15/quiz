<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizGame extends Model
{
    use HasFactory;

    protected $table = 'quiz_game';

    protected $fillable = [
        'name',
        'status',
    ];
}