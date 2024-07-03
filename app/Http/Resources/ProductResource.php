<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Color;
use App\Models\Brand;
use App\Models\ProductCategory;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $colors = Color::whereIn('id', $this->color)->get();
        $brand = Brand::where('id',$this->brand)->first();
        $category = ProductCategory::where('id',$this->category)->first();
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'slug' => $this->slug,
            'price' => $this->price,
            'category' => new ProductCategoryResource($category),
            'brand' => new BrandResource($brand),
            'quantity' => $this->quantity,
            'sold' => $this->sold ?? 0,
            'images' => MediaResource::collection($this->whenLoaded('media')),
            'colors' => ColorResource::collection($colors),
            'tags' => $this->tags,
            'total_ratings' => $this->total_ratings ?? 0,
            'ratings' => RatingResource::collection($this->whenLoaded('ratings')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
