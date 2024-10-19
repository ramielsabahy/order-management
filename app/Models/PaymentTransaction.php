<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $guarded = ['id'];

    const STATUS_CREATED = 'created';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELED = 'canceled';
}
