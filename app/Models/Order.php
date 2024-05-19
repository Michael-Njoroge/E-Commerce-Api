<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory,HasUuids;

    protected $guarded = [];

    public function products()
    {
       return $this->belongsToMany(Product::class, 'order_products')
                    ->withPivot('count', 'color', 'price')
                    ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
