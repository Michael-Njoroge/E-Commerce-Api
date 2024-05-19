<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->pivot->cart_id,
            'product_id' => $this->pivot->product_id,
            'count' => $this->pivot->count,
            'color' => $this->pivot->color,
            'price' => $this->pivot->price,
        ];
    }
}
