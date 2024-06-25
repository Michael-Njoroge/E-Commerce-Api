<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory,HasUuids;

    protected $guarded = [];

    public function products(): BelongsToMany
    {
       return $this->belongsToMany(Product::class, 'order_products', 'order_id', 'product_id')
                    ->withPivot('count', 'color', 'price')
                    ->withTimestamps();;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
