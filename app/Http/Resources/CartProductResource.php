<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Product;
use App\Models\Color;

class CartProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = Product::with('media','ratings','ratings.user')->find($this->pivot->product_id);
        $color = Color::findOrFail($this->pivot->color);
        return [
            'id' => $this->pivot->cart_id,
            'product' => new ProductResource($product),
            'quantity' => $this->pivot->quantity,
            'color' => $color,
            'price' => $this->pivot->price,
        ];
    }
}
