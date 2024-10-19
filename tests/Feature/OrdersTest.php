<?php
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Laravel\Passport\Passport;
use App\Models\PaymentTransaction;
use App\Services\PayPalPaymentService;
use App\Models\Order;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a user for testing and set them as authenticated
    $this->user = User::factory()->create([
        'email' => 'ramyelsabahy95@gmail.com',
        'password' => bcrypt('password123'), // Password for authentication
    ]);

    Passport::actingAs($this->user);
});

it('can create an order and returns the expected response', function () {
    // Set up the request data
    $requestData = [
        'status' => 'Pending',
        'product_name' => 'Phone',
        'quantity' => 5,
        'price' => 5,
    ];

    // Mock the PayPalPaymentService to return a predictable response
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

    // Send a POST request to the orders endpoint
    $response = $this->postJson('/api/orders', $requestData);

    // Assert that the response status is 200 OK
    $response->assertStatus(200);

    // Assert that the response structure is as expected
    $response->assertJsonStructure([
        'data' => [
            'data' => [
                'status',
                'order_id',
                'order_status',
                'approve_href',
            ],
        ],
    ]);

    // Assert the content of the response
    $response->assertJson([
        'data' => [
            'data' => [
                'status' => 'OK',
                'order_id' => '551558689C1779356',
                'order_status' => 'CREATED',
                'approve_href' => 'https://www.sandbox.paypal.com/checkoutnow?token=551558689C1779356',
            ],
        ],
    ]);

    // Assert that the payment transaction was created
    $this->assertDatabaseHas('payment_transactions', [
        'paypal_order_id' => '551558689C1779356',
        'amount' => 25, // quantity * price
        'currency' => 'USD',
        'user_id' => $this->user->id,
        'status' => PaymentTransaction::STATUS_CREATED,
    ]);
});

it('returns validation errors for invalid data', function () {
    // Set up invalid request data (missing fields)
    $requestData = [
        'quantity' => 5
    ];

    // Send a POST request to the orders endpoint
    $response = $this->postJson('/api/orders', $requestData);

    // Assert that the response status is 422 Unprocessable Entity
    $response->assertStatus(422);

    // Assert that the response contains validation errors
    $response->assertJsonStructure([
        'message',
        'errors' => [
            'product_name',
            'price',
        ],
    ]);
});

it('can list orders with pagination', function () {
    // Create multiple orders for the user
    Order::factory()->count(10)->create([
        'user_id' => $this->user->id,
        'product_name' => 'Phone',
        'price' => 5,
        'quantity' => 5,
        'status' => 'Pending',
    ]);

    // Create one order with a different status
    Order::factory()->create([
        'user_id' => $this->user->id,
        'product_name' => 'Phone',
        'price' => 5,
        'quantity' => 5,
        'status' => 'Pending',
    ]);

    // Send a GET request to the orders endpoint with pagination
    $response = $this->getJson('/api/orders?per_page=10&page=1');

    // Assert that the response status is 200 OK
    $response->assertStatus(200);

    // Assert that the response structure is as expected
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

    // Assert that the response contains the correct number of orders
    $this->assertCount(10, $response->json('data'));

    // Assert that the first order in the response has a status of 'Paid'
    $this->assertEquals('Pending', $response->json('data.0.status'));
});

it('returns an empty array if no orders exist', function () {
    // Send a GET request to the orders endpoint for a user with no orders
    $response = $this->getJson('/api/orders?per_page=10&page=1');

    // Assert that the response status is 200 OK
    $response->assertStatus(200);

    // Assert that the response contains an empty data array
    $response->assertJson([
        'data' => [],
    ]);
});
