<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class BusinessProfileController extends Controller
{
    public function edit(): View
    {
        $profile = BusinessProfile::query()->findOrFail(1);
        $qrisImageUrl = $profile->qris_image_path
            ? Storage::disk('public')->url($profile->qris_image_path)
            : null;

        return view('admin.business-profile.edit', compact('profile', 'qrisImageUrl'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_toko' => ['nullable', 'string', 'max:120'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'nomor_hp' => ['nullable', 'string', 'max:50'],
            'jam_operasional' => ['nullable', 'string', 'max:100'],
            'biaya_ongkir' => ['nullable', 'integer', 'min:0'],
            'harga_dasar_custom' => ['required', 'integer', 'min:0'],
            'maks_buah_custom' => ['required', 'integer', 'min:1'],
            'masa_berlaku_voucher_hari' => ['required', 'integer', 'min:1'],
            'merchant_name' => ['nullable', 'string', 'max:120'],
            'qris_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'qris_image.image' => 'Gambar QRIS harus berupa gambar.',
            'qris_image.mimes' => 'Gambar QRIS hanya boleh berformat JPG atau PNG.',
            'qris_image.max' => 'Ukuran gambar QRIS maksimal 2 MB.',
        ]);

        if (array_key_exists('biaya_ongkir', $data) && $data['biaya_ongkir'] !== null) {
            $data['biaya_ongkir'] = (int) $data['biaya_ongkir'];
        } else {
            unset($data['biaya_ongkir']);
        }

        if (empty($data['nama_toko'])) {
            $data['nama_toko'] = 'Queen Degan Jelly';
        }

        if (empty($data['merchant_name'])) {
            $data['merchant_name'] = $data['nama_toko'];
        }

        if ($request->hasFile('qris_image')) {
            $path = $request->file('qris_image')->store('qris', 'public');

            if (! is_string($path) || $path === '') {
                throw new RuntimeException('Gambar QRIS gagal disimpan.');
            }

            $data['qris_image_path'] = $path;
        }

        unset($data['qris_image']);
        $previousImage = DB::transaction(function () use ($data): ?string {
            $profile = BusinessProfile::query()->lockForUpdate()->findOrFail(1);
            $previousImage = $profile->qris_image_path;
            $profile->update($data);

            return $previousImage;
        }, attempts: 5);

        if (isset($data['qris_image_path'])
            && is_string($previousImage)
            && ! filter_var($previousImage, FILTER_VALIDATE_URL)
            && Storage::disk('public')->exists($previousImage)
            && ! Storage::disk('public')->delete($previousImage)) {
            throw new RuntimeException('Gambar QRIS lama gagal dihapus dari penyimpanan.');
        }

        return redirect()->route('admin.business-profile.edit')->with('success', 'Pengaturan usaha berhasil diperbarui.');
    }
}
