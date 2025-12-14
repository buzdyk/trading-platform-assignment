<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol->code,
            'side' => $this->side,
            'price' => $this->price,
            'amount' => $this->amount,
            'status' => $this->status,
            'user' => $this->whenLoaded('user', fn () => $this->user->name),
            'created_at' => $this->created_at,
        ];
    }
}
