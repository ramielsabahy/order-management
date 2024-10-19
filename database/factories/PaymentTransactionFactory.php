<?php

namespace Database\Factories;

use App\Enums\OrderStatusEnum;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paypal_order_id' => fake()->randomNumber(),
            'currency' => 'USD',
            'amount' => rand(1, 100),
            'status' => PaymentTransaction::STATUS_CREATED
        ];
    }
}
