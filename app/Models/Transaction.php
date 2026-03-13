<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    // List of attributes that can be mass-assigned from the frontend
    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'description',
        'type',
        'transaction_date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
