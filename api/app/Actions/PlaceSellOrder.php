<?php

namespace App\Actions;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PlaceSellOrder
{
    public function __construct(
        private MatchOrder $matchOrder
    ) {}

    public function __invoke(User $user, int $symbolId, string $price, string $amount): Order
    {
        return DB::transaction(function () use ($user, $symbolId, $price, $amount): Order {
            $asset = Asset::lockForUpdate()
                ->where('user_id', $user->id)
                ->where('symbol_id', $symbolId)
                ->first();

            if (! $asset) {
                throw new InvalidArgumentException('No asset found for this symbol');
            }

            $availableAmount = bcsub($asset->amount, $asset->locked_amount, 8);

            if (bccomp($availableAmount, $amount, 8) < 0) {
                throw new InvalidArgumentException('Insufficient asset balance');
            }

            $asset->locked_amount = bcadd($asset->locked_amount, $amount, 8);
            $asset->save();

            $order = Order::create([
                'user_id' => $user->id,
                'symbol_id' => $symbolId,
                'side' => Order::SIDE_SELL,
                'price' => $price,
                'amount' => $amount,
                'status' => Order::STATUS_OPEN,
            ]);

            ($this->matchOrder)($order);

            return $order->fresh(['symbol']);
        });
    }
}
