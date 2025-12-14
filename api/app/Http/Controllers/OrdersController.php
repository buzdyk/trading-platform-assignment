<?php

namespace App\Http\Controllers;

use App\Actions\CancelOrder;
use App\Actions\PlaceBuyOrder;
use App\Actions\PlaceSellOrder;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use InvalidArgumentException;

class OrdersController extends Controller
{
    public function __construct(
        private PlaceBuyOrder $placeBuyOrder,
        private PlaceSellOrder $placeSellOrder,
        private CancelOrder $cancelOrder
    ) {}

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

        try {
            $action = $validated['side'] === Order::SIDE_BUY
                ? $this->placeBuyOrder
                : $this->placeSellOrder;

            $order = $action(
                $user,
                $validated['symbol_id'],
                $validated['price'],
                $validated['amount']
            );

            return response()->json([
                'order' => new OrderResource($order),
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        $this->authorize('cancel', $order);

        try {
            ($this->cancelOrder)($order);

            return response()->json([
                'message' => 'Order cancelled successfully',
                'order' => new OrderResource($order->fresh(['symbol'])),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
