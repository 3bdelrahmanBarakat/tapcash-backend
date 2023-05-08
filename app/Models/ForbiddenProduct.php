<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForbiddenProduct extends Model
{
    use HasFactory;
    protected $table = "forbidden_products";
    protected $fillable = ['kid_id', 'parent_id', 'product_id'];

    public function user()
    {
        return $this->belongsToMany(User::class);
    }
}
