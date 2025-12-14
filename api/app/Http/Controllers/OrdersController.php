<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrdersController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Order::where('status', Order::STATUS_OPEN)
            ->with('symbol', 'user:id,name');

        if ($request->has('symbol_id')) {
            $query->where('symbol_id', $request->input('symbol_id'));
        }

        $orders = $query->orderBy('price', 'desc')->get();

        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $order = Order::create([
            'user_id' => $user->id,
            'symbol_id' => $validated['symbol_id'],
            'side' => $validated['side'],
            'price' => $validated['price'],
            'amount' => $validated['amount'],
            'status' => Order::STATUS_OPEN,
        ]);

        $order->load('symbol');

        return response()->json([
            'order' => new OrderResource($order),
        ], 201);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', Order::STATUS_OPEN)
            ->firstOrFail();

        $order->update(['status' => Order::STATUS_CANCELLED]);

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
            ],
        ]);
    }
}
