<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PlaceBuyOrder
{
    public function __construct(
        private MatchOrder $matchOrder
    ) {}

    public function __invoke(User $user, int $symbolId, string $price, string $amount): Order
    {
        return DB::transaction(function () use ($user, $symbolId, $price, $amount): Order {
            $user = User::lockForUpdate()->find($user->id);

            $total = bcmul($price, $amount, 8);
            $availableBalance = bcsub($user->balance, $user->locked_balance, 8);

            if (bccomp($availableBalance, $total, 8) < 0) {
                throw new InvalidArgumentException('Insufficient balance');
            }

            $user->locked_balance = bcadd($user->locked_balance, $total, 8);
            $user->save();

            $order = Order::create([
                'user_id' => $user->id,
                'symbol_id' => $symbolId,
                'side' => Order::SIDE_BUY,
                'price' => $price,
                'amount' => $amount,
                'status' => Order::STATUS_OPEN,
            ]);

            ($this->matchOrder)($order);

            return $order->fresh(['symbol']);
        });
    }
}
