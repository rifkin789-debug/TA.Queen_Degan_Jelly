<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Str;

class OrderCodeGenerator
{
    public function generate(): string
    {
        do {
            $orderCode = 'QDJ-'.now()->format('Ymd').'-'.Str::upper(Str::random(10));
        } while (Order::query()->where('order_code', $orderCode)->exists());

        return $orderCode;
    }
}
