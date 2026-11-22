<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Fruit;
use App\Services\CustomOrderService;
use App\Services\QrisPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomOrderController extends Controller
{
    public function create(QrisPaymentService $qrisPaymentService): View
    {
        $profile = BusinessProfile::query()->findOrFail(1);
        $fruits = Fruit::query()
            ->where('aktif', true)
            ->where('stok_porsi', '>', 0)
            ->orderBy('nama_buah')
            ->get(['id', 'nama_buah', 'harga_porsi', 'stok_porsi']);

        session(['custom_payment_code_preview' => $qrisPaymentService->previewUniqueCode()]);

        return view('custom.create', [
            'fruits' => $fruits,
            'basePrice' => $profile->harga_dasar_custom,
            'maximumFruits' => $profile->maks_buah_custom,
            'shippingCost' => $profile->biaya_ongkir ?? (int) config('shop.shipping_cost', 10000),
            'paymentCodePreview' => session('custom_payment_code_preview'),
        ]);
    }

    public function store(Request $request, CustomOrderService $customOrderService): RedirectResponse
    {
        $profile = BusinessProfile::query()->findOrFail(1);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){4})\+?[0-9][0-9\s().-]{6,28}[0-9]$/'],
            'fulfillment_method' => ['required', Rule::in(['ambil', 'antar'])],
            'address' => ['nullable', 'required_if:fulfillment_method,antar', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:500'],
            'buah' => ['required', 'array', 'min:1', 'max:'.$profile->maks_buah_custom],
            'buah.*' => ['required', 'integer', 'distinct', Rule::exists('buah', 'id')],
            'jumlah_batok' => ['required', 'integer', 'min:1'],
        ], [
            'buah.min' => 'Pilih minimal satu jenis buah.',
            'buah.max' => 'Jumlah buah melebihi batas pilihan custom.',
            'buah.*.distinct' => 'Pilihan buah tidak boleh duplikat.',
            'address.required_if' => 'Alamat wajib diisi untuk metode antar.',
            'phone.regex' => 'Nomor HP tidak valid.',
        ]);

        $preferredPaymentCode = session()->pull('custom_payment_code_preview');
        $order = $customOrderService->create(
            $data,
            is_int($preferredPaymentCode) ? $preferredPaymentCode : null,
        );

        $paymentUrl = URL::temporarySignedRoute(
            'payments.qris.show',
            $order->payment->expires_at,
            ['order' => $order->id],
        );

        return redirect()->to($paymentUrl);
    }
}
