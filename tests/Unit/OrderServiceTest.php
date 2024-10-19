<?php
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Enums\OrderStatusEnum;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->orderService = new OrderService();
});
it('Store New Order', function () {
    User::factory()->create();
    $data = [
        'user_id' => 1,
        'product_name' => 'Sample Product',
        'quantity' => 2,
        'price' => 150.00,
        'status' => OrderStatusEnum::PENDING,
    ];
    expect(0)->toEqual(Order::count());
    $order = $this->orderService->store($data);
    expect($order)->toBeInstanceOf(Order::class)
        ->and(1)->toEqual(Order::count())
        ->and($data['product_name'])->toEqual($order->product_name);
});

it('can list user orders with filters and pagination', function () {
    $user = User::factory()->create();
    Order::factory()->count(15)->create(['user_id' => $user->id]);

    $filterData = [
        'per_page' => 10,
        'page' => 1
    ];

    request()->merge($filterData);

    $orders = $this->orderService->list($filterData, $user);

    expect($orders)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($orders->count())->toBe(10)
        ->and($orders->total())->toBe(15);
});

it('can update an existing order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id, 'product_name' => 'Old Product Name']);

    $updateData = [
        'status'    => OrderStatusEnum::PAID->value,
    ];

    $this->orderService->update($updateData, $order);

    $order->refresh();

    expect($order->status)->toBe(OrderStatusEnum::PAID->value);
});
