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
    private const STATUS_LABELS = [
        Order::STATUS_OPEN => 'open',
        Order::STATUS_FILLED => 'filled',
        Order::STATUS_CANCELLED => 'cancelled',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'symbol_id' => $this->symbol_id,
            'symbol' => $this->symbol->code,
            'side' => $this->side,
            'price' => $this->price,
            'amount' => $this->amount,
            'status' => self::STATUS_LABELS[$this->status] ?? 'unknown',
            'user' => $this->whenLoaded('user', fn () => $this->user->name),
            'created_at' => $this->created_at,
        ];
    }
}
