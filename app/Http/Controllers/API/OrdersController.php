<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ListOrdersRequest;
use App\Http\Requests\API\StoreOrderRequest;
use App\Http\Requests\API\UpdateOrderRequest;
use App\Http\Resources\API\OrderResource;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\OrderService;
use App\Services\PayPalPaymentService;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;

class OrdersController extends BaseAPIController
{
    public function __construct(public PaypalPaymentService $paymentService)
    {

    }

    public function store(StoreOrderRequest $request, OrderService $orderService)
    {
        $storedOrder = $orderService->store(data: $request->validated()+['user_id' => auth()->user()->id]);
        $token = $this->paymentService->getToken();
        $paypalOrder = $this->paymentService->placeOrder($request->validated(), token: $token);
        PaymentTransaction::create([
            'paypal_order_id' => $paypalOrder['order_id'],
            'order_id'  => $storedOrder->id,
            'amount' => $request->get('quantity') * $request->get('price'),
            'currency' => 'USD',
            'user_id' => auth()->user()->id,
            'status' => PaymentTransaction::STATUS_CREATED,
        ]);
        return $this->successResponse([
            'data' => $paypalOrder,
        ]);
    }

    public function index(ListOrdersRequest $request, OrderService $orderService)
    {
        $user = auth()->user();
        $orders = $orderService->list(data: $request->validated(), user: $user);
        return $this->successResponse(data: OrderResource::collection($orders));
    }

    public function update(Order $order, UpdateOrderRequest $request, OrderService $orderService)
    {
        $orderService->update(data: $request->validated(), order: $order);
    }
}
