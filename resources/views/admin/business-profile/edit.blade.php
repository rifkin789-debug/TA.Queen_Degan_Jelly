@extends('layouts.admin')

@section('title', 'Pengaturan Usaha | Queen Degan Jelly')

@section('admin-content')
<div class="mb-4">
    <h1 class="h3 fw-bold text-dark mb-1">Pengaturan Profil Usaha</h1>
    <p class="text-muted small">Pengaturan Usaha: atur nama toko, alamat dapur, kontak WhatsApp, jam operasional, parameter custom, voucher, dan pembayaran QRIS.</p>
</div>

<div class="qdj-card p-4" style="max-width: 800px;">
    <form method="POST" action="{{ route('admin.business-profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- 1. Profil Toko & Kontak Footer -->
        <div class="p-3 rounded-4 mb-3" style="background-color: var(--qdj-mint);">
            <h2 class="h6 fw-bold text-dark mb-1">🏪 Informasi Toko & Kontak Publik</h2>
            <span class="small text-muted">Informasi nama toko, alamat, nomor WhatsApp, dan jam operasional yang tampil pada footer halaman pelanggan.</span>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-dark" for="nama_toko">Nama Toko</label>
                <input class="form-control" id="nama_toko" name="nama_toko" type="text" value="{{ old('nama_toko', $profile->nama_toko ?? 'Queen Degan Jelly') }}" placeholder="Contoh: Queen Degan Jelly" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-dark" for="nomor_hp">Nomor HP / WhatsApp</label>
                <input class="form-control" id="nomor_hp" name="nomor_hp" type="text" value="{{ old('nomor_hp', $profile->nomor_hp ?? '+62 812-3456-7890') }}" placeholder="Contoh: +62 812-3456-7890">
            </div>
            <div class="col-12">
                <label class="form-label small fw-semibold text-dark" for="alamat">Alamat Toko / Dapur</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="2" placeholder="Contoh: Muncar, Banyuwangi, Jawa Timur, Indonesia">{{ old('alamat', $profile->alamat ?? "Muncar, Banyuwangi\nJawa Timur, Indonesia") }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label small fw-semibold text-dark" for="jam_operasional">Jam Operasional</label>
                <input class="form-control" id="jam_operasional" name="jam_operasional" type="text" value="{{ old('jam_operasional', ($profile->jam_operasional && !str_contains(strtolower($profile->jam_operasional), 'setiap hari')) ? $profile->jam_operasional : 'Senin - Sabtu: 08:00 - 17:00 WIB') }}" placeholder="Contoh: Senin - Sabtu: 08:00 - 17:00 WIB">
                <span class="small text-muted">Jam buka toko/dapur yang tampil pada footer website pelanggan.</span>
            </div>
        </div>

        <!-- 2. Pengiriman & Ongkos Kirim (Ongkir) -->
        <div class="p-3 rounded-4 mb-3" style="background-color: var(--qdj-mint);">
            <h2 class="h6 fw-bold text-dark mb-1">🛵 Pengiriman & Ongkos Kirim (Ongkir)</h2>
            <span class="small text-muted">Tarif biaya kirim standar untuk pengantaran langsung ke alamat pelanggan (area Muncar dan sekitarnya).</span>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-dark" for="biaya_ongkir">Tarif Ongkos Kirim Standar (Rupiah)</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">Rp</span>
                    <input class="form-control" id="biaya_ongkir" name="biaya_ongkir" type="number" min="0" step="500" value="{{ old('biaya_ongkir', $profile->biaya_ongkir ?? 10000) }}" placeholder="Contoh: 10000" required>
                </div>
                <span class="small text-muted d-block mt-1">Isi <strong>0</strong> jika Anda sedang mengadakan promo <em>Gratis Ongkir</em>.</span>
            </div>
        </div>

        <div class="p-3 rounded-4 mb-4" style="background-color: var(--qdj-mint);">
            <h2 class="h6 fw-bold text-dark mb-1">🥥 Degan Jelly Custom</h2>
            <span class="small text-muted">Parameter default untuk kustomisasi isian buah mandiri.</span>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-dark" for="harga_dasar_custom">Harga dasar per batok (rupiah)</label>
                <input class="form-control" id="harga_dasar_custom" name="harga_dasar_custom" type="number" min="0" value="{{ old('harga_dasar_custom', $profile->harga_dasar_custom) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-dark" for="maks_buah_custom">Maksimal jenis buah</label>
                <input class="form-control" id="maks_buah_custom" name="maks_buah_custom" type="number" min="1" value="{{ old('maks_buah_custom', $profile->maks_buah_custom) }}" required>
            </div>
        </div>

        <div class="p-3 rounded-4 mb-3" style="background-color: var(--qdj-mint);">
            <h2 class="h6 fw-bold text-dark mb-1">🎁 Voucher Hadiah</h2>
            <span class="small text-muted">Durasi kedaluwarsa voucher sejak pembelian berhasil.</span>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-semibold text-dark" for="masa_berlaku_voucher_hari">Masa berlaku voucher (hari)</label>
            <input class="form-control" id="masa_berlaku_voucher_hari" name="masa_berlaku_voucher_hari" type="number" min="1" value="{{ old('masa_berlaku_voucher_hari', $profile->masa_berlaku_voucher_hari) }}" required>
        </div>

        <div class="p-3 rounded-4 mb-3" style="background-color: var(--qdj-mint);">
            <h2 class="h6 fw-bold text-dark mb-1">💳 Pembayaran QRIS</h2>
            <span class="small text-muted">QRIS mitra usaha untuk pembayaran transfer manual/verifikasi bukti.</span>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold text-dark" for="merchant_name">Nama Merchant / Toko QRIS</label>
            <input class="form-control" id="merchant_name" name="merchant_name" type="text" value="{{ old('merchant_name', $profile->merchant_name ?? 'Queen Degan Jelly') }}" placeholder="Contoh: Queen Degan Jelly">
        </div>

        <div class="mb-4">
            <label class="form-label small fw-semibold text-dark" for="qris_image">Gambar QRIS (JPG/PNG, maksimal 2 MB)</label>
            <input class="form-control" id="qris_image" name="qris_image" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
            @if($qrisImageUrl)
                <div class="mt-3">
                    <img class="img-fluid rounded-4 border shadow-sm" src="{{ $qrisImageUrl }}" alt="QRIS pembayaran saat ini" style="max-height: 280px;">
                </div>
            @else
                <div class="form-text small text-muted mt-2">QRIS belum diunggah. Pembayaran QRIS belum dapat dilakukan.</div>
            @endif
        </div>

        <button class="btn btn-qdj px-5 py-2" type="submit">Simpan Pengaturan</button>
    </form>
</div>
@endsection
