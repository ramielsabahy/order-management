<?php
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\PayPalPaymentService;
use Illuminate\Support\Facades\Http;
use App\Models\PaymentTransaction;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->paypalService = new PayPalPaymentService();
});

it('can get an access token from PayPal', function () {
    // Mock the HTTP response for the token request
    Http::fake([
        config('services.paypal.endpoint_v1') . '/oauth2/token' => Http::sequence()
            ->push(['access_token' => 'mocked_access_token'])
    ]);

    // Call the method to get the token
    $token = $this->paypalService->getToken();

    // Assert that the returned token is correct
    expect($token)->toBe('mocked_access_token');
});

it('can place an order with PayPal', function () {
    // Mock the HTTP response for placing an order
    Http::fake([
        config('services.paypal.endpoint_v2') . '/checkout/orders' => Http::sequence()
            ->push([
                "id" => "mocked_order_id",
                "status" => "CREATED",
                "links" => [
                    ["rel" => "approve", "href" => "https://paypal.com/approve-link"]
                ]
            ])
    ]);

    // Sample data for the order
    $data = [
        'quantity' => 2,
        'price' => 100.00,
    ];

    // Mock the return and cancel URLs
    config(['PAYPAL_LOCAL_RETURN_URL' => route('paypal.success')]);
    config(['PAYPAL_LOCAL_CANCEL_URL' => route('paypal.cancel')]);

    // Call the method to place the order
    $response = $this->paypalService->placeOrder($data, 'mocked_access_token');

    // Assert that the response contains the expected values
    expect($response)->toBe([
        'status' => 'OK',
        'order_id' => 'mocked_order_id',
        'order_status' => 'CREATED',
        'approve_href' => 'https://paypal.com/approve-link'
    ]);
});

it('can confirm a payment with PayPal', function () {
    // Mock the HTTP response for the confirmation request
    Http::fake([
        config('services.paypal.endpoint_v2') . '/checkout/orders/mocked_order_id/capture' => Http::sequence()
            ->push(['status' => 'COMPLETED'])
    ]);
    $user = \App\Models\User::factory()->create();
    $order = \App\Models\Order::factory()->create(['user_id' => $user->id]);
    // Assuming a PaymentTransaction with the mocked order ID exists
    PaymentTransaction::factory()->create([
        'paypal_order_id' => 'mocked_order_id',
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => PaymentTransaction::STATUS_CREATED,
    ]);

    // Call the method to confirm the payment
    $response = $this->paypalService->confirmPayment('mocked_order_id');

    // Assert that the response indicates payment is completed
    expect($response)->toBe(['status' => 'COMPLETED']);

    // Assert that the PaymentTransaction status is updated
    $transaction = PaymentTransaction::where('paypal_order_id', 'mocked_order_id')->first();
    expect($transaction->status)->toBe(PaymentTransaction::STATUS_PAID);
});

it('can retrieve the correct link from the PayPal response', function () {
    $links = [
        ["rel" => "approve", "href" => "https://paypal.com/approve"],
        ["rel" => "self", "href" => "https://paypal.com/self"]
    ];

    // Call the method to retrieve the approve link
    $link = $this->paypalService->getLink($links, "approve");

    // Assert that the correct link is returned
    expect($link)->toBe("https://paypal.com/approve");
});

it('returns null when the link does not exist', function () {
    $links = [
        ["rel" => "self", "href" => "https://paypal.com/self"]
    ];

    // Call the method to retrieve a non-existent link
    $link = $this->paypalService->getLink($links, "approve");

    // Assert that null is returned
    expect($link)->toBeNull();
});
