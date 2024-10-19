<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Services\PayPalPaymentService;
use Illuminate\Http\Request;

class PayPalController extends BaseAPIController
{
    public function __construct(public PaypalPaymentService $paymentService)
    {

    }
    public function paypalSuccess(Request $request)
    {
        $this->paymentService->confirmPayment($request->get('token'));
        return $this->successResponse([
            'data' => [
                'message' => 'Payment success'
            ]
        ]);
    }

    public function paypalCancel(Request $request)
    {
        PaymentTransaction::where('paypal_order_id', $request->get('token'))->update([
            'status' => PaymentTransaction::STATUS_CANCELED,
        ]);
        return $this->errorResponse('Payment cancelled');
    }

}
