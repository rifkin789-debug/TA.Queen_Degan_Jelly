@extends('layouts.admin')

@section('title', ($package->exists ? 'Ubah Paket Voucher' : 'Tambah Paket Voucher') . ' | Queen Degan Jelly')

@section('admin-content')
<div class="mb-4">
    <a href="{{ route('admin.paket-voucher.index') }}" class="text-decoration-none small text-muted">
        ← Kembali ke Kelola Paket Voucher
    </a>
    <h1 class="h3 fw-bold text-dark mt-1">{{ $package->exists ? 'Ubah Paket Voucher' : 'Tambah Paket Voucher' }}</h1>
    <p class="text-muted small">Tentukan nama paket, harga total, batok, dan batas kustomisasi buah.</p>
</div>

<div class="qdj-card p-4" style="max-width: 800px;">
    <form method="POST" action="{{ $package->exists ? route('admin.paket-voucher.update', $package) : route('admin.paket-voucher.store') }}">
        @csrf
        @if($package->exists)
            @method('PUT')
        @endif

        <div class="mb-3">
            <label class="form-label small fw-semibold text-dark" for="nama_paket">Nama paket</label>
            <input class="form-control" id="nama_paket" name="nama_paket" value="{{ old('nama_paket', $package->nama_paket) }}" placeholder="Misal: Paket Single Segar (1 Batok)" maxlength="255" required>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-dark" for="harga">Harga paket (rupiah)</label>
                <input class="form-control" id="harga" name="harga" type="number" min="1" value="{{ old('harga', $package->harga) }}" placeholder="25000" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-dark" for="jumlah_batok">Jumlah batok</label>
                <input class="form-control" id="jumlah_batok" name="jumlah_batok" type="number" min="1" value="{{ old('jumlah_batok', $package->jumlah_batok ?? 1) }}" placeholder="1" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-dark" for="maks_buah">Maksimal jenis buah</label>
                <input class="form-control" id="maks_buah" name="maks_buah" type="number" min="1" value="{{ old('maks_buah', $package->maks_buah ?? 3) }}" placeholder="3" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-dark" for="stok">Stok voucher</label>
                <input class="form-control" id="stok" name="stok" type="number" min="0" value="{{ old('stok', $package->stok ?? 10) }}" placeholder="10" required>
                <div class="form-text small">0 = Habis (tidak bisa dibeli)</div>
            </div>
        </div>
        <div class="form-check p-3 bg-light rounded-3 mb-4">
            <input class="form-check-input ms-0 me-2" id="aktif" name="aktif" type="checkbox" value="1" @checked(old('aktif', $package->aktif ?? true))>
            <label class="form-check-label small fw-semibold text-dark" for="aktif">Paket aktif (tampil di halaman Beli Voucher)</label>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-qdj px-4" type="submit">Simpan</button>
            <a class="btn btn-qdj-outline px-4" href="{{ route('admin.paket-voucher.index') }}">Kembali</a>
        </div>
    </form>
</div>
@endsection
