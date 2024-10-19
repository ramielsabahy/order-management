<?php

namespace App\Services;

use App\Http\Requests\API\ListOrdersRequest;
use App\Http\Requests\API\UpdateOrderRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function store(array $data): Order
    {
        return Order::create($data);
    }

    public function list(array $data, User $user): LengthAwarePaginator
    {
        return $user->orders()->filters($data)->paginate(
            request('per_page', 10),
            '*',
            '',
            request('page', 1)
        );
    }

    public function update(array $data, Order $order)
    {
        $order->update($data);
    }
}
