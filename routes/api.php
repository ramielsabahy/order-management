<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\OrdersController;
use App\Http\Controllers\API\PayPalController;
use App\Http\Controllers\API\AuthController;

Route::group(['middleware' => 'throttle:20,1'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::any('paypal/success', [PayPalController::class, 'paypalSuccess'])->name('paypal.success');
    Route::any('paypal/cancel', [PayPalController::class, 'paypalCancel'])->name('paypal.cancel');
    Route::middleware('auth:api')->group(function () {
        Route::post('/orders', [OrdersController::class, 'store']);
        Route::get('/orders', [OrdersController::class, 'index']);
        Route::put('/orders/{order}', [OrdersController::class, 'update']);
    });
});
