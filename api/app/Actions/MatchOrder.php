<?php

namespace App\Actions;

use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;

class MatchOrder
{
    public function __invoke(Order $order): ?Trade
    {
        if (!$order->isOpen()) {
            return null;
        }

        $counterOrder = $this->findCounterOrder($order);

        if (!$counterOrder) {
            return null;
        }

        // No nested transaction - already in one from PlaceBuyOrder/PlaceSellOrder
        return $this->executeTrade($order, $counterOrder);
    }

    private function findCounterOrder(Order $order): ?Order
    {
        $query = Order::where('symbol_id', $order->symbol_id)
            ->where('status', Order::STATUS_OPEN)
            ->where('user_id', '!=', $order->user_id)
            ->where('amount', $order->amount) // No partial matching - amounts must be equal
            ->lockForUpdate();

        if ($order->isBuy()) {
            // New BUY → match with first SELL where sell.price <= buy.price
            return $query
                ->where('side', Order::SIDE_SELL)
                ->where('price', '<=', $order->price)
                ->orderBy('price', 'asc')
                ->orderBy('created_at', 'asc')
                ->first();
        } else {
            // New SELL → match with first BUY where buy.price >= sell.price
            return $query
                ->where('side', Order::SIDE_BUY)
                ->where('price', '>=', $order->price)
                ->orderBy('price', 'desc')
                ->orderBy('created_at', 'asc')
                ->first();
        }
    }

    private function executeTrade(Order $newOrder, Order $counterOrder): Trade
    {
        // Re-lock the counter order to ensure fresh state within this transaction
        $counterOrder = Order::lockForUpdate()->findOrFail($counterOrder->id);

        $buyOrder = $newOrder->isBuy() ? $newOrder : $counterOrder;
        $sellOrder = $newOrder->isSell() ? $newOrder : $counterOrder;

        // Use the sell order's price (maker gets their price)
        $tradePrice = $sellOrder->price;
        $tradeAmount = $sellOrder->amount;
        $total = bcmul($tradePrice, $tradeAmount, 8);

        $commissionRate = config('trading.commission_rate');
        $commission = bcmul($tradeAmount, (string) $commissionRate, 8);
        $buyerReceives = bcsub($tradeAmount, $commission, 8);

        // Lock users
        $buyer = User::lockForUpdate()->find($buyOrder->user_id);
        $seller = User::lockForUpdate()->find($sellOrder->user_id);

        // Transfer USD: buyer's locked_balance -> seller's balance
        $buyerLockedTotal = bcmul($buyOrder->price, $buyOrder->amount, 8);
        $buyer->locked_balance = bcsub($buyer->locked_balance, $buyerLockedTotal, 8);
        $buyer->balance = bcsub($buyer->balance, $total, 8);
        $buyer->save();

        $seller->balance = bcadd($seller->balance, $total, 8);
        $seller->save();

        // Transfer assets: seller's locked_amount -> buyer's amount (minus commission)
        $sellerAsset = Asset::lockForUpdate()
            ->where('user_id', $seller->id)
            ->where('symbol_id', $sellOrder->symbol_id)
            ->firstOrFail();

        $sellerAsset->locked_amount = bcsub($sellerAsset->locked_amount, $tradeAmount, 8);
        $sellerAsset->amount = bcsub($sellerAsset->amount, $tradeAmount, 8);
        $sellerAsset->save();

        // Add to buyer's asset (create if doesn't exist)
        $buyerAsset = Asset::lockForUpdate()
            ->firstOrCreate(
                ['user_id' => $buyer->id, 'symbol_id' => $buyOrder->symbol_id],
                ['amount' => '0', 'locked_amount' => '0']
            );

        $buyerAsset->amount = bcadd($buyerAsset->amount, $buyerReceives, 8);
        $buyerAsset->save();

        // Mark orders as filled (observer broadcasts updates)
        $buyOrder->update(['status' => Order::STATUS_FILLED]);
        $sellOrder->update(['status' => Order::STATUS_FILLED]);

        return Trade::create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol_id' => $buyOrder->symbol_id,
            'price' => $tradePrice,
            'amount' => $tradeAmount,
            'total' => $total,
            'commission' => $commission,
        ]);
    }
}
