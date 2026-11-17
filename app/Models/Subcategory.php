<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = [
        'tenant_id',
        'category_id',
        'name',
        'icon',
        'description',
        'total_question',
        'total_quiz',
        'display_order',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}