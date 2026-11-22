<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VoucherPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VoucherPackageController extends Controller
{
    public function index(): View
    {
        $packages = VoucherPackage::query()->orderBy('nama_paket')->paginate(20);

        return view('admin.voucher-packages.index', compact('packages'));
    }

    public function create(): View
    {
        return view('admin.voucher-packages.form', ['package' => new VoucherPackage]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        if (! array_key_exists('stok', $data) || $data['stok'] === null) {
            $data['stok'] = 10;
        }

        VoucherPackage::query()->create($data);

        return redirect()->route('admin.paket-voucher.index')->with('success', 'Paket voucher berhasil ditambahkan.');
    }

    public function edit(VoucherPackage $paket_voucher): View
    {
        return view('admin.voucher-packages.form', ['package' => $paket_voucher]);
    }

    public function update(Request $request, VoucherPackage $paket_voucher): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($paket_voucher, $data): void {
            $lockedPackage = VoucherPackage::query()->lockForUpdate()->findOrFail($paket_voucher->id);
            if (! array_key_exists('stok', $data) || $data['stok'] === null) {
                unset($data['stok']);
            }
            $lockedPackage->update($data);
        }, attempts: 5);

        return redirect()->route('admin.paket-voucher.index')->with('success', 'Paket voucher berhasil diperbarui.');
    }

    public function adjustStock(Request $request, VoucherPackage $paket_voucher): RedirectResponse
    {
        $data = $request->validate([
            'delta' => ['nullable', 'integer', 'between:-10000,10000'],
            'stok' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        if (! array_key_exists('delta', $data) && ! array_key_exists('stok', $data)) {
            return back()->with('error', 'Masukkan perubahan atau jumlah stok yang valid.');
        }

        $newStock = DB::transaction(function () use ($paket_voucher, $data): int {
            $lockedPackage = VoucherPackage::query()->lockForUpdate()->findOrFail($paket_voucher->id);

            if (isset($data['delta'])) {
                $target = max(0, $lockedPackage->stok + (int) $data['delta']);
            } else {
                $target = (int) $data['stok'];
            }

            $lockedPackage->update(['stok' => $target]);

            return $target;
        }, attempts: 5);

        return back()->with('success', "Stok paket {$paket_voucher->nama_paket} diperbarui menjadi {$newStock}.");
    }

    public function toggleStatus(VoucherPackage $paket_voucher): RedirectResponse
    {
        $isActive = DB::transaction(function () use ($paket_voucher): bool {
            $lockedPackage = VoucherPackage::query()->lockForUpdate()->findOrFail($paket_voucher->id);
            $isActive = ! $lockedPackage->aktif;
            $lockedPackage->update(['aktif' => $isActive]);

            return $isActive;
        }, attempts: 5);

        return back()->with(
            'success',
            $isActive ? 'Paket voucher berhasil diaktifkan.' : 'Paket voucher berhasil dinonaktifkan.'
        );
    }

    /**
     * @return array{nama_paket: string, harga: int|string, jumlah_batok: int|string, maks_buah: int|string, stok?: int|string|null, aktif: bool}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'nama_paket' => ['required', 'string', 'max:255'],
            'harga' => ['required', 'integer', 'min:1'],
            'jumlah_batok' => ['required', 'integer', 'min:1'],
            'maks_buah' => ['required', 'integer', 'min:1'],
            'stok' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        return $data + ['aktif' => $request->boolean('aktif')];
    }
}
