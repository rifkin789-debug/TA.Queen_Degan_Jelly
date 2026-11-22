@extends('layouts.admin')

@section('title', ($fruit->exists ? 'Ubah Buah' : 'Tambah Buah') . ' | Queen Degan Jelly')

@section('admin-content')
<div class="mb-4">
    <a href="{{ route('admin.buah.index') }}" class="text-decoration-none small text-muted">
        ← Kembali ke Kelola Buah
    </a>
    <h1 class="h3 fw-bold text-dark mt-1">{{ $fruit->exists ? 'Ubah Buah' : 'Tambah Buah' }}</h1>
    <p class="text-muted small">Atur nama varian buah, harga per porsi, dan ketersediaan stok.</p>
</div>

<div class="qdj-card p-4" style="max-width: 720px;">
    <form method="POST" action="{{ $fruit->exists ? route('admin.buah.update', $fruit) : route('admin.buah.store') }}">
        @csrf
        @if($fruit->exists)
            @method('PUT')
        @endif

        <div class="mb-3">
            <label class="form-label small fw-semibold text-dark" for="nama_buah">Nama buah</label>
            <input class="form-control" id="nama_buah" name="nama_buah" value="{{ old('nama_buah', $fruit->nama_buah) }}" placeholder="Contoh: Anggur, Mangga, Leci, Stroberi" maxlength="255" required>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-dark" for="harga_porsi">Harga per porsi (rupiah)</label>
                <input class="form-control" id="harga_porsi" name="harga_porsi" type="number" min="0" value="{{ old('harga_porsi', $fruit->harga_porsi) }}" placeholder="3000" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-dark" for="stok_porsi">Stok (porsi)</label>
                <input class="form-control" id="stok_porsi" name="stok_porsi" type="number" min="0" value="{{ old('stok_porsi', $fruit->stok_porsi ?? 0) }}" placeholder="50" required>
            </div>
        </div>
        <div class="form-check p-3 bg-light rounded-3 mb-4">
            <input class="form-check-input ms-0 me-2" id="aktif" name="aktif" type="checkbox" value="1" @checked(old('aktif', $fruit->aktif ?? true))>
            <label class="form-check-label small fw-semibold text-dark" for="aktif">Buah aktif (tampil di pilihan kustomisasi)</label>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-qdj px-4" type="submit">Simpan</button>
            <a class="btn btn-qdj-outline px-4" href="{{ route('admin.buah.index') }}">Kembali</a>
        </div>
    </form>
</div>
@endsection
