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
    Http::fake([
        config('services.paypal.endpoint_v1') . '/oauth2/token' => Http::sequence()
            ->push(['access_token' => 'mocked_access_token'])
    ]);

    $token = $this->paypalService->getToken();

    expect($token)->toBe('mocked_access_token');
});

it('can place an order with PayPal', function () {
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

    $data = [
        'quantity' => 2,
        'price' => 100.00,
    ];

    config(['PAYPAL_LOCAL_RETURN_URL' => route('paypal.success')]);
    config(['PAYPAL_LOCAL_CANCEL_URL' => route('paypal.cancel')]);

    $response = $this->paypalService->placeOrder($data, 'mocked_access_token');

    expect($response)->toBe([
        'status' => 'OK',
        'order_id' => 'mocked_order_id',
        'order_status' => 'CREATED',
        'approve_href' => 'https://paypal.com/approve-link'
    ]);
});

it('can confirm a payment with PayPal', function () {
    Http::fake([
        config('services.paypal.endpoint_v2') . '/checkout/orders/mocked_order_id/capture' => Http::sequence()
            ->push(['status' => 'COMPLETED'])
    ]);
    $user = \App\Models\User::factory()->create();
    $order = \App\Models\Order::factory()->create(['user_id' => $user->id]);
    PaymentTransaction::factory()->create([
        'paypal_order_id' => 'mocked_order_id',
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => PaymentTransaction::STATUS_CREATED,
    ]);

    $response = $this->paypalService->confirmPayment('mocked_order_id');

    expect($response)->toBe(['status' => 'COMPLETED']);

    $transaction = PaymentTransaction::where('paypal_order_id', 'mocked_order_id')->first();
    expect($transaction->status)->toBe(PaymentTransaction::STATUS_PAID);
});

it('can retrieve the correct link from the PayPal response', function () {
    $links = [
        ["rel" => "approve", "href" => "https://paypal.com/approve"],
        ["rel" => "self", "href" => "https://paypal.com/self"]
    ];

    $link = $this->paypalService->getLink($links, "approve");

    expect($link)->toBe("https://paypal.com/approve");
});

it('returns null when the link does not exist', function () {
    $links = [
        ["rel" => "self", "href" => "https://paypal.com/self"]
    ];

    $link = $this->paypalService->getLink($links, "approve");

    expect($link)->toBeNull();
});
