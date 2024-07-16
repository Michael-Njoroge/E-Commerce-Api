<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory,HasUuids;

    protected $guarded = [];

    protected $casts = [
        'color' => 'json',
        'tags' => 'json',
    ];

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

    public function category()
    {
        return $this->belongsTo(ProductCategory::class,'category');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class,'brand');
    }

    public function carts()
    {
        return $this->belongsToMany(Cart::class, 'cart_product')
                    ->withPivot('count', 'color', 'price')
                    ->withTimestamps();
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_products', 'order_id', 'product_id')
                    ->withPivot('count', 'color', 'price')
                    ->withTimestamps();
    }
}
