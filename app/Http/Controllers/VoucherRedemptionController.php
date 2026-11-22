<?php

namespace App\Http\Controllers;

use App\Models\Fruit;
use App\Models\Voucher;
use App\Services\VoucherLookupService;
use App\Services\VoucherPurchaseService;
use App\Services\VoucherRedemptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VoucherRedemptionController extends Controller
{
    public function enterCodeForm(): View
    {
        return view('vouchers.redeem-enter');
    }

    public function enterCode(Request $request, VoucherLookupService $voucherLookupService): RedirectResponse
    {
        $data = $request->validate([
            'kode_voucher' => ['required', 'string'],
        ]);
        $voucher = $voucherLookupService->find($data['kode_voucher'], $request->ip());

        if (! $voucher) {
            return back()
                ->withInput()
                ->withErrors(['lookup' => VoucherLookupService::ERROR_MESSAGE]);
        }

        return redirect()->route('vouchers.redeem.show', ['kode' => $voucher->kode_voucher]);
    }

    public function show(
        Request $request,
        string $kode,
        VoucherLookupService $voucherLookupService,
        VoucherPurchaseService $voucherPurchaseService,
    ): View {
        $voucher = $voucherLookupService->find($kode, $request->ip());
        $fruits = collect();
        $error = null;

        if (! $voucher) {
            $error = VoucherLookupService::ERROR_MESSAGE;
        } else {
            $voucher = $voucherPurchaseService->expireActiveVoucherIfDue($voucher->id);

            if ($voucher->status === Voucher::STATUS_ACTIVE) {
                $fruits = Fruit::query()
                    ->where('aktif', true)
                    ->where('stok_porsi', '>=', $voucher->jumlah_batok)
                    ->orderBy('nama_buah')
                    ->get(['id', 'nama_buah', 'harga_porsi', 'stok_porsi']);
            } else {
                $error = $this->statusMessage($voucher);
            }
        }

        return view('vouchers.redeem', compact('voucher', 'fruits', 'error'));
    }

    public function store(
        Request $request,
        string $kode,
        VoucherLookupService $voucherLookupService,
        VoucherPurchaseService $voucherPurchaseService,
        VoucherRedemptionService $voucherRedemptionService,
    ): View|RedirectResponse {
        $voucher = $voucherLookupService->find($kode, $request->ip());

        if (! $voucher) {
            return back()
                ->withInput()
                ->withErrors(['lookup' => VoucherLookupService::ERROR_MESSAGE]);
        }

        $voucher = $voucherPurchaseService->expireActiveVoucherIfDue($voucher->id);

        if ($voucher->status !== Voucher::STATUS_ACTIVE) {
            return back()
                ->withInput()
                ->withErrors(['voucher' => $this->statusMessage($voucher)]);
        }

        $data = $request->validate([
            'buah' => ['required', 'array', 'min:1', 'max:'.$voucher->maks_buah],
            'buah.*' => ['required', 'integer', 'distinct', Rule::exists('buah', 'id')],
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){4})\+?[0-9][0-9\s().-]{6,28}[0-9]$/'],
            'fulfillment_method' => ['required', Rule::in(['ambil', 'antar'])],
            'address' => ['nullable', 'required_if:fulfillment_method,antar', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'buah.min' => 'Pilih minimal satu jenis buah.',
            'buah.max' => 'Jumlah buah melebihi batas pilihan voucher.',
            'buah.*.distinct' => 'Pilihan buah tidak boleh duplikat.',
            'customer_name.required' => 'Nama penerima wajib diisi.',
            'phone.regex' => 'Nomor HP penerima tidak valid.',
            'address.required_if' => 'Alamat wajib diisi untuk metode antar.',
        ]);

        $order = $voucherRedemptionService->redeem($voucher->id, $data);

        return view('vouchers.redeemed', compact('order'));
    }

    private function statusMessage(Voucher $voucher): string
    {
        return match ($voucher->status) {
            Voucher::STATUS_EXPIRED => 'Masa berlaku voucher telah berakhir.',
            Voucher::STATUS_USED => 'Voucher ini sudah digunakan.',
            Voucher::STATUS_WAITING_PAYMENT,
            Voucher::STATUS_WAITING_VERIFICATION => 'Voucher belum aktif.',
            default => 'Voucher tidak dapat ditukarkan.',
        };
    }
}
