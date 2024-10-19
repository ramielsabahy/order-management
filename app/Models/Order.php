<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilters($query, $filters)
    {
        $query->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->when($filters['date_from'] ?? null, function ($query, $date_from) {
            $query->whereDate('created_at', '>=', $date_from);
        })->when($filters['date_to'] ?? null, function ($query, $date_to) {
            $query->whereDate('created_at', '<=', $date_to);
        });
    }
}
