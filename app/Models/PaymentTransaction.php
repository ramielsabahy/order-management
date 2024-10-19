<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    const STATUS_CREATED = 'created';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELED = 'canceled';

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
