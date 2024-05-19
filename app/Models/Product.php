<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory,HasUuids;

    protected $guarded = [];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'medially');
    }

    public function carts()
    {
        return $this->belongsToMany(Cart::class, 'cart_product')
                    ->withPivot('count', 'color', 'price')
                    ->withTimestamps();
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_products')
                    ->withPivot('count', 'color', 'price')
                    ->withTimestamps();
    }
}
