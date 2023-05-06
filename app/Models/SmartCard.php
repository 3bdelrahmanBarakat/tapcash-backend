<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartCard extends Model
{
    use HasFactory;
    protected $table ="smartcards";
    protected $fillable = [
        'user_id',
        'card_number',
        'validity_date',
        'cvv',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
