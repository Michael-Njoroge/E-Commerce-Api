<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'slug' => $this->slug,
            'price' => $this->price,
            'category' => $this->category,
            'brand' => $this->brand,
            'quantity' => $this->quantity,
            'sold' => $this->sold ?? 0,
            'images' => $this->images ?? [],
            'color' => $this->color ?? [],
            'tag' => $this->tag ?? [],
            'total_ratings' => $this->total_ratings,
            'ratings' => RatingResource::collection($this->whenLoaded('ratings')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
