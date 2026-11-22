<?php

namespace App\Console\Commands;

use App\Services\CustomOrderService;
use App\Services\VoucherPurchaseService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:expire-custom-orders')]
#[Description('Batalkan pesanan custom dan voucher yang tidak dibayar dalam dua jam.')]
class ExpireCustomOrders extends Command
{
    public function handle(
        CustomOrderService $customOrderService,
        VoucherPurchaseService $voucherPurchaseService,
    ): int {
        $expiredCount = $customOrderService->expireDueOrders();
        $expiredVoucherCount = $voucherPurchaseService->expireDuePayments();
        $this->info(($expiredCount + $expiredVoucherCount).' pembayaran custom/voucher kedaluwarsa.');

        return self::SUCCESS;
    }
}
