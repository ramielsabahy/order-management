<?php
use App\Http\Controllers\API\PayPalController;
use App\Models\PaymentTransaction;
use App\Services\PayPalPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mock the PayPalPaymentService
    $this->paymentService = $this->createMock(PayPalPaymentService::class);
    $user = \App\Models\User::factory()->create();
    \App\Models\Order::factory()->create(['user_id' => $user->id]);
    // Create the PayPalController instance with the mocked service
    $this->controller = new PayPalController($this->paymentService);
});

it('successfully confirms payment on paypalSuccess', function () {
    // Expect the confirmPayment method to be called with the correct token
    $this->paymentService
        ->expects($this->once())
        ->method('confirmPayment')
        ->with('fake_token');

    // Create a request with the token
    $request = Request::create('/api/paypal/success', 'POST', ['token' => 'fake_token']);

    // Call the paypalSuccess method
    $response = $this->controller->paypalSuccess($request);

    // Assert the response
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals(
        ['data' => ['data' => ['message' => 'Payment success']]],
        $response->getOriginalContent()
    );
});

it('updates payment transaction status on paypalCancel', function () {
    // Create a PaymentTransaction record
    PaymentTransaction::create([
        'paypal_order_id' => 'fake_order_id',
        'order_id' => 1,
        'amount' => 100,
        'currency' => 'USD',
        'user_id' => 1,
        'status' => PaymentTransaction::STATUS_CREATED,
    ]);

    // Create a request with the token
    $request = Request::create('/api/paypal/cancel', 'POST', ['token' => 'fake_order_id']);

    // Call the paypalCancel method
    $response = $this->controller->paypalCancel($request);

    // Assert the response
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals(
        ['error' => 'Payment cancelled'],
        $response->getOriginalContent()
    );
});
