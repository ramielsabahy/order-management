<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'product_name'  => $this->product_name,
            'price'         => $this->price,
            'quantity'      => $this->quantity,
            'status'        => $this->status,
            'user'          => new UserResource($this->whenLoaded('user')),
        ];
    }
}
