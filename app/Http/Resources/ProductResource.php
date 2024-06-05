<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Color;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $color = Color::where('id',$this->color)->first();
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
            'images' => MediaResource::collection($this->whenLoaded('media')),
            'colors' => new ColorResource($color) ?? [],
            'tags' => $this->tags ?? [],
            'total_ratings' => $this->total_ratings ?? 0,
            'ratings' => RatingResource::collection($this->whenLoaded('ratings')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
