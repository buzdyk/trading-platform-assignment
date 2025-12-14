<?php

namespace App\Actions;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelOrder
{
    public function __invoke(Order $order): void
    {
        if (! $order->isOpen()) {
            throw new InvalidArgumentException('Order is not open');
        }

        DB::transaction(function () use ($order): void {
            $user = User::lockForUpdate()->find($order->user_id);

            if ($order->isBuy()) {
                $this->releaseBuyOrderFunds($user, $order);
            } else {
                $this->releaseSellOrderAssets($order);
            }

            // Observer broadcasts update
            $order->update(['status' => Order::STATUS_CANCELLED]);
        });
    }

    private function releaseBuyOrderFunds(User $user, Order $order): void
    {
        $lockedAmount = bcmul($order->price, $order->amount, 8);
        $user->locked_balance = bcsub($user->locked_balance, $lockedAmount, 8);
        $user->save();
    }

    private function releaseSellOrderAssets(Order $order): void
    {
        $asset = Asset::lockForUpdate()
            ->where('user_id', $order->user_id)
            ->where('symbol_id', $order->symbol_id)
            ->firstOrFail();

        $asset->locked_amount = bcsub($asset->locked_amount, $order->amount, 8);
        $asset->save();
    }
}
