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
        return [
        'id' => $this->id,
        'user' => new UserResource($this->whenLoaded('user')),
        'shipping_info' => new ShippingInfoResource($this->whenLoaded('shippingInfo')),
        'payment_info' => new PaymentInfoResource($this->whenLoaded('paymentInfo')),
        'payed_at' => $this->payed_at,
        'total_price' => $this->total_price,
        'total_price_after' => $this->total_price_after,
        'order_status' => $this->order_status,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
        'items' => OrderItemResource::collection($this->whenLoaded('items')),
        'updated_at' => $this->updated_at,
        'created_at' => $this->created_at
        ];
    }
}
