<?php
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Laravel\Passport\Passport;
use App\Models\PaymentTransaction;
use App\Services\PayPalPaymentService;
use App\Models\Order;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'ramyelsabahy95@gmail.com',
        'password' => bcrypt('password123'), // Password for authentication
    ]);

    Passport::actingAs($this->user);
});

it('can create an order and returns the expected response', function () {
    $requestData = [
        'status' => 'Pending',
        'product_name' => 'Phone',
        'quantity' => 5,
        'price' => 5,
    ];

    $this->mock(PayPalPaymentService::class, function ($mock) {
        $mock->shouldReceive('getToken')
            ->once()
            ->andReturn('fake-token');

        $mock->shouldReceive('placeOrder')
            ->once()
            ->with($this->anything(), 'fake-token')
            ->andReturn([
                'status' => 'OK',
                'order_id' => '551558689C1779356',
                'order_status' => 'CREATED',
                'approve_href' => 'https://www.sandbox.paypal.com/checkoutnow?token=551558689C1779356',
            ]);
    });

    $response = $this->postJson('/api/orders', $requestData);

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data' => [
            'status',
            'order_id',
            'order_status',
            'approve_href',
        ],
    ]);

    $response->assertJson([
        'data' => [
            'status' => 'OK',
            'order_id' => '551558689C1779356',
            'order_status' => 'CREATED',
            'approve_href' => 'https://www.sandbox.paypal.com/checkoutnow?token=551558689C1779356',
        ],
    ]);

    $this->assertDatabaseHas('payment_transactions', [
        'paypal_order_id' => '551558689C1779356',
        'amount' => 25, // quantity * price
        'currency' => 'USD',
        'user_id' => $this->user->id,
        'status' => PaymentTransaction::STATUS_CREATED,
    ]);
});

it('returns validation errors for invalid data', function () {
    $requestData = [
        'quantity' => 5
    ];

    $response = $this->postJson('/api/orders', $requestData);

    $response->assertStatus(422);

    $response->assertJsonStructure([
        'error',
    ]);
});

it('can list orders with pagination', function () {
    Order::factory()->count(10)->create([
        'user_id' => $this->user->id,
        'product_name' => 'Phone',
        'price' => 5,
        'quantity' => 5,
        'status' => 'Pending',
    ]);

    Order::factory()->create([
        'user_id' => $this->user->id,
        'product_name' => 'Phone',
        'price' => 5,
        'quantity' => 5,
        'status' => 'Pending',
    ]);

    $response = $this->getJson('/api/orders?per_page=10&page=1');

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'product_name',
                'price',
                'quantity',
                'status',
            ],
        ],
    ]);

    $this->assertCount(10, $response->json('data'));

    $this->assertEquals('Pending', $response->json('data.0.status'));
});

it('returns an empty array if no orders exist', function () {
    $response = $this->getJson('/api/orders?per_page=10&page=1');

    $response->assertStatus(200);

    $response->assertJson([
        'data' => [],
    ]);
});
