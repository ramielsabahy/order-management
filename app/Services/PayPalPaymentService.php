<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Support\Facades\Http;

class PayPalPaymentService
{
    public function getToken(): string
    {
        $response = Http::withBasicAuth(config('services.paypal.client_id'), config('services.paypal.client_secret'))
            ->asForm()
            ->post(config('services.paypal.endpoint_v1') . '/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);
        return $response->json()['access_token'];
    }
    public function placeOrder(array $data, string $token)
    {
        $returnUrl = env('PAYPAL_LOCAL_RETURN_URL');
        $cancelUrl = env('PAYPAL_LOCAL_CANCEL_URL');
        $response = Http::withToken($token)
            ->post(config('services.paypal.endpoint_v2').'/checkout/orders', [
                "intent" => "CAPTURE",
                "purchase_units" => [
                    [
                        "amount" => [
                            "currency_code" => "USD",
                            "value" => $data['quantity'] * $data['price'],
                        ]
                    ]
                ],
                "application_context" => [
                    "locale" => "en-US",
                    "shipping_preference" => "NO_SHIPPING",
                    "payment_method" => [
                        "payer_selected" => "PAYPAL",
                        "payee_preferred" => "UNRESTRICTED"
                    ],
                    "return_url" => $returnUrl,
                    "cancel_url" => $cancelUrl
                ]
            ]);
        $response = $response->json();
        $data = [
            'status' => 'OK',
            'order_id' => $response["id"],
            'order_status' => $response["status"],
            'approve_href' => $this->getLink($response["links"], "approve")
        ];
        return $data;
    }

    public function confirmPayment(string $paypalOrderId)
    {
        $token = $this->getToken();
        $response = Http::withToken($token)->withBody("", 'application/json')
            ->post(config('services.paypal.endpoint_v2').'/checkout/orders/'.$paypalOrderId.'/capture');
        $transaction = PaymentTransaction::where('paypal_order_id', $paypalOrderId)->first();
        $transaction->update([
            'status' => PaymentTransaction::STATUS_PAID,
        ]);

        $transaction->order->user->notify(new OrderStatusChangedNotification(transaction: $transaction));

        return $response->json();
    }

    public function getLink($links, $rel)
    {
        if (is_array($links)) {
            foreach ($links as $link) {
                if ($link['rel'] == $rel) {
                    return $link['href'];
                }
            }
        }
        return null;
    }
}
