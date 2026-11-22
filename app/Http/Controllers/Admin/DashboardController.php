<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\VoucherPurchaseService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(VoucherPurchaseService $voucherPurchaseService): View
    {
        $voucherPurchaseService->expireDueActiveVouchers();

        $totalProducts = Product::count();
        $totalOrders = Order::count();
        $pendingOrders = Order::where('payment_status', 'pending')->count();
        $catalogRevenue = Order::query()
            ->where('payment_status', 'paid')
            ->where('type', 'Katalog')
            ->sum('total');
        $customRevenue = Order::query()
            ->where('payment_status', 'paid')
            ->where('type', 'Custom')
            ->sum('total');
        $voucherRevenue = Payment::query()
            ->where('transaction_status', 'paid')
            ->whereHas('voucher', fn ($query) => $query->whereIn('status', [
                Voucher::STATUS_ACTIVE,
                Voucher::STATUS_USED,
            ]))
            ->sum('gross_amount');
        $totalRevenue = $catalogRevenue + $customRevenue + $voucherRevenue;
        $latestOrders = Order::latest()->take(8)->get();

        return view('admin.dashboard', compact(
            'totalProducts',
            'totalOrders',
            'pendingOrders',
            'catalogRevenue',
            'customRevenue',
            'voucherRevenue',
            'totalRevenue',
            'latestOrders',
        ));
    }
}
