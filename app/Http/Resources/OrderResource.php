<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $paymentIntent = json_decode($this->payment_intent, true);
        $user = User::find($this->user_id);
        return [
        'id' => $this->id,
        'payment_intent' => $paymentIntent,
        'order_status' => $this->order_status,
        'orderedBy' => new UserResource($user),
        'products' => ProductResource::collection($this->whenLoaded('products')),
        'updated_at' => $this->updated_at,
        'created_at' => $this->created_at
        ];
    }
}
