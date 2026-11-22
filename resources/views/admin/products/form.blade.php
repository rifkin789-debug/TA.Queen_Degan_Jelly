@extends('layouts.admin')

@section('title', ($product->exists ? 'Ubah Produk' : 'Tambah Produk') . ' | Queen Degan Jelly')

@section('admin-content')
<div class="mb-4">
    <a href="{{ route('admin.produk.index') }}" class="text-decoration-none small text-muted">
        ← Kembali ke Kelola Produk
    </a>
    <h1 class="h3 fw-bold text-dark mt-1">
        {{ $product->exists ? 'Ubah Produk' : 'Tambah Produk Baru' }}
    </h1>
    <p class="text-muted small">Lengkapi informasi produk degan jelly di bawah ini.</p>
</div>

<div class="qdj-card p-4">
    <form method="POST"
          enctype="multipart/form-data"
          action="{{ $product->exists ? route('admin.produk.update', $product) : route('admin.produk.store') }}">
        @csrf
        @if($product->exists)
            @method('PUT')
        @endif

        <div class="row g-4">
            <!-- Kolom 1: Nama, Kategori, Harga dasar, Masa simpan -->
            <div class="col-lg-6">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark" for="name">Nama Produk</label>
                    <input class="form-control"
                           id="name"
                           name="name"
                           value="{{ old('name', $product->name) }}"
                           placeholder="Contoh: Degan Jelly Original"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark" for="category_id">Kategori</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">Tanpa Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                    data-name="{{ strtolower($category->name) }}"
                                    data-slug="{{ $category->slug }}"
                                    @selected(old('category_id', $product->category_id) == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3" id="jumlahBatokWrapper">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label small fw-semibold text-dark mb-0" for="jumlah_batok">
                            Isi Batok Kelapa per Paket
                        </label>
                        <span class="badge rounded-pill bg-light text-dark border small" id="paketBadge">Paket</span>
                    </div>
                    <div class="input-group">
                        <input type="number"
                               min="1"
                               max="30"
                               class="form-control"
                               id="jumlah_batok"
                               name="jumlah_batok"
                               value="{{ old('jumlah_batok', $product->jumlah_batok ?? 1) }}"
                               placeholder="Contoh: 3"
                               required>
                        <span class="input-group-text bg-light small">Batok</span>
                    </div>
                    <span class="small text-muted d-block mt-1" id="jumlahBatokHint">
                        Tentukan berapa batok kelapa dalam 1 paket ini (misal: <strong>3</strong> batok). Customer pada kategori <strong>Paket</strong> akan langsung mendapatkan <strong>3</strong> form pilihan isian buah terpisah untuk masing-masing batok.
                    </span>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark" for="price">Harga Dasar (Rp)</label>
                    <input type="number"
                           min="1"
                           class="form-control"
                           id="price"
                           name="price"
                           value="{{ old('price', $product->price) }}"
                           placeholder="15000"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark" for="shelf_life_hours">Masa Simpan dalam Jam</label>
                    <input type="number"
                           min="1"
                           class="form-control"
                           id="shelf_life_hours"
                           name="shelf_life_hours"
                           value="{{ old('shelf_life_hours', $product->shelf_life_hours ?? 96) }}"
                           placeholder="96">
                    <span class="small text-muted">Standar kesegaran Queen Degan Jelly adalah 96 jam sejak waktu produksi batch.</span>
                </div>
            </div>

            <!-- Kolom 2: Status stok, Deskripsi, Foto produk + Ganti foto -->
            <div class="col-lg-6">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label small fw-semibold text-dark" for="stock">Status Stok (Jumlah Batok)</label>
                        <input type="number"
                               min="0"
                               class="form-control"
                               id="stock"
                               name="stock"
                               value="{{ old('stock', $product->stock ?? 0) }}"
                               required>
                    </div>
                    <div class="col-sm-6 d-flex align-items-end">
                        <div class="form-check p-3 bg-light rounded-3 w-100 mb-1">
                            <input class="form-check-input ms-0 me-2"
                                   type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   @checked(old('is_active', $product->is_active ?? true))>
                            <label class="form-check-label small fw-semibold" for="is_active">
                                Produk Aktif di Katalog
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark" for="description">Deskripsi Produk</label>
                    <textarea class="form-control"
                              id="description"
                              name="description"
                              rows="3"
                              placeholder="Deskripsi singkat kelezatan dan keaslian produk...">{{ old('description', $product->description) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark" for="image">Foto Produk</label>
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 overflow-hidden bg-light border" style="width: 80px; height: 80px; flex-shrink: 0;">
                            @if($product->imageUrl())
                                <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="w-100 h-100" style="object-fit: cover;">
                            @else
                                <img src="{{ asset('images/tentang-kami-raw.jpg') }}" alt="Placeholder" class="w-100 h-100" style="object-fit: cover; object-position: 25% 45%;">
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <input class="form-control form-control-sm"
                                   id="image"
                                   type="file"
                                   name="image"
                                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <span class="small text-muted d-block mt-1">
                                {{ $product->exists ? 'Pilih file baru untuk "Ganti foto". Format JPG, PNG, atau WebP (Maks 2 MB).' : 'Format JPG, PNG, atau WebP (Maks 2 MB).' }}
                            </span>
                            <span class="small text-muted d-block mt-1 fst-italic">
                                * Catatan: Untuk kategori Original & Mix, 1 foto yang Anda unggah di sini otomatis digunakan bersama untuk kedua varian (Original dan Mix).
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi: Batal & Simpan Perubahan -->
        <div class="d-flex justify-content-end gap-2 pt-4 mt-3 border-top">
            <a class="btn btn-qdj-outline px-4" href="{{ route('admin.produk.index') }}">
                Batal
            </a>
            <button class="btn btn-qdj px-4" type="submit">
                {{ $product->exists ? 'Simpan Perubahan' : 'Simpan Produk' }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const categorySelect = document.getElementById('category_id');
    const paketBadge = document.getElementById('paketBadge');
    const hint = document.getElementById('jumlahBatokHint');

    function checkCategory() {
        if (! categorySelect) return;
        const selectedOpt = categorySelect.options[categorySelect.selectedIndex];
        const catName = selectedOpt ? (selectedOpt.getAttribute('data-name') || selectedOpt.text.toLowerCase()) : '';
        const isPaket = catName.includes('paket');

        if (isPaket) {
            paketBadge.className = 'badge rounded-pill bg-warning text-dark border small';
            paketBadge.textContent = 'Khusus Paket';
            hint.innerHTML = 'Tentukan berapa batok kelapa dalam 1 paket ini (misal: <strong>3</strong> batok). Customer pada kategori <strong>Paket</strong> akan langsung mendapatkan <strong>3</strong> form pilihan isian buah terpisah untuk masing-masing batok.';
        } else {
            paketBadge.className = 'badge rounded-pill bg-light text-muted border small';
            paketBadge.textContent = 'Satuan (1 Batok)';
            hint.innerHTML = 'Standar untuk produk satuan adalah 1 batok kelapa.';
        }
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', checkCategory);
        checkCategory();
    }
});
</script>
@endpush
