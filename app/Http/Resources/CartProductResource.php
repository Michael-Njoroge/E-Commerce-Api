<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Product;

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
        return [
            'id' => $this->pivot->cart_id,
            'product' => new ProductResource($product),
            'count' => $this->pivot->count,
            'color' => $this->pivot->color,
            'price' => $this->pivot->price,
        ];
    }
}
