<?php

namespace App\Http\Resources;

use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Trade
 */
class TradeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userId = $request->user()->id;
        $isBuyer = $this->buyer_id === $userId;

        return [
            'id' => $this->id,
            'symbol' => $this->symbol->code,
            'side' => $isBuyer ? 'buy' : 'sell',
            'price' => $this->price,
            'amount' => $this->amount,
            'total' => $this->total,
            'commission' => $isBuyer ? $this->commission : '0',
            'created_at' => $this->created_at,
        ];
    }
}
