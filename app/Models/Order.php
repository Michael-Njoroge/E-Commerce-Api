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

     public function shippingInfo()
    {
        return $this->belongsTo(ShippingInfo::class);
    }

    public function paymentInfo()
    {
        return $this->belongsTo(PaymentInfo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
