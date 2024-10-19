<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePaymentService
{
    public function createPaymentIntent(array $data)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $paymentIntent = PaymentIntent::create([
            'amount' => $data['price'] * $data['quantity'] * 100,
            'currency' => 'usd',
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);

        return [
            'payment_intent_id' => $paymentIntent->id,
            'client_secret' => $paymentIntent->client_secret,
        ];
    }

    public function confirmPayment(array $data)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        try {
            $paymentIntent = PaymentIntent::retrieve($data['payment_intent_id']);
            $paymentIntent->confirm([
                'return_url' => env('APP_URL') . '/api/order-management/stripe-payment/confirm',
            ]);
            if ($paymentIntent->status === 'succeeded') {
                PaymentTransaction::create([
                    'user_id' => auth()->id(),
                    'payment_intent_id' => $paymentIntent->id,
                    'amount' => $paymentIntent->amount,
                    'status' => 'succeeded',
                ]);

                return response()->json(['success' => true, 'message' => 'Payment succeeded']);
            } else {
                return response()->json(['success' => false, 'message' => 'Payment failed']);
            }
        }catch (CardException $exception){
            return response()->json(['success' => false, 'error' => $exception->getError()->message]);
        }
    }
}
