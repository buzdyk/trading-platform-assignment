<?php

namespace App\Observers;

use App\Events\OrderUpdated;
use App\Models\Order;

class OrderObserver
{
    public function created(Order $order): void
    {
        OrderUpdated::dispatch($order->load('symbol'));
    }

    public function updated(Order $order): void
    {
        OrderUpdated::dispatch($order->load('symbol'));
    }
}
