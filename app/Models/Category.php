<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'parent_id', 'icon',
        'display_order', 'status', 'description',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    // public function subcategories()
    // {
    //     return $this->hasMany(Subcategory::class);
    // }


    public function quizzes()
    {
        return $this->hasMany(Quiz::class, 'category_id'); 
    }
}
