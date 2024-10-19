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
    // Create a user and some orders for testing
    $user = User::factory()->create();
    Order::factory()->count(15)->create(['user_id' => $user->id]);

    // Simulate pagination data
    $filterData = [
        'per_page' => 10,
        'page' => 1
    ];

    // Mock the request pagination query parameters
    request()->merge($filterData);

    // Call the list method to get the paginated orders
    $orders = $this->orderService->list($filterData, $user);

    // Assert that the result is a LengthAwarePaginator instance
    expect($orders)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($orders->count())->toBe(10)
        ->and($orders->total())->toBe(15);
});

it('can update an existing order', function () {
    // Create a user and an order for testing
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id, 'product_name' => 'Old Product Name']);

    // Data to update the order
    $updateData = [
        'status'    => OrderStatusEnum::PAID->value,
    ];

    // Call the update method to update the order
    $this->orderService->update($updateData, $order);

    // Refresh the order instance to get the updated data
    $order->refresh();

    // Assert that the order was updated successfully
    expect($order->status)->toBe(OrderStatusEnum::PAID->value);
});
