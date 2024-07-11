<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingInfoResource extends JsonResource
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
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'other' => $this->other,
            'pincode' => $this->pincode,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
