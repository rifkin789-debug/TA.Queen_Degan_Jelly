@extends('layouts.app')

@section('title', ($isOriginal ?? false ? ($variantsData[request('varian') === 'mix' ? 'mix' : 'original']['name'] ?? $product->name) : $product->name) . ' | Queen Degan Jelly')

@push('styles')
<style>
/* Styling Segmented Button / Pill Selector Varian (Tema Hijau Queen Degan Jelly) */
.variant-segmented-container {
    background-color: #E8F3EC;
    border: 1.5px solid var(--qdj-primary-border);
    padding: 4px;
    display: inline-flex;
    width: 100%;
    max-width: 380px;
}
.variant-pill-btn {
    border: none;
    background: transparent;
    color: #4B5563;
    font-size: 0.92rem;
    font-weight: 600;
    padding: 0.55rem 1.25rem;
    border-radius: 50rem;
    transition: all 0.22s ease-in-out;
    cursor: pointer;
    text-align: center;
}
.variant-pill-btn:hover:not(.active) {
    color: var(--qdj-primary);
    background-color: rgba(35, 130, 83, 0.1);
}
.variant-pill-btn.active {
    background-color: var(--qdj-primary) !important;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(35, 130, 83, 0.32);
}
</style>
@endpush

@section('content')
@php
    $catSlug = $product->category?->slug ?? '';
    $catName = strtolower($product->category?->name ?? '');
    $isRasa = ($catSlug === 'rasa' || str_contains($catName, 'rasa'));
    $isPaket = (in_array($catSlug, ['paket', 'paket-mix'], true) || str_contains($catName, 'paket'));
    $isOriginal = ! $isRasa && ! $isPaket;
    $packageBatok = $product->packageBatokCount();
    $maxStock = min(20, max(1, $product->stock));

    // =========================================================================
    // ATURAN 7: Dukung URL parameter (contoh: ?varian=mix)
    // =========================================================================
    $urlVariant = strtolower(request('varian', ''));
    $selectedVariant = in_array($urlVariant, ['mix', 'original'], true) ? $urlVariant : 'original';

    // =========================================================================
    // ATURAN 4: Data per varian disimpan dalam satu objek/array agar mudah diedit
    // Foto produk disamakan sesuai foto yang diatur di laman admin (1 rumpun Original & Mix)
    // =========================================================================
    $basePriceVal = (float) $product->price;
    $productSharedImage = $product->imageUrl() ?: asset('images/tentang-kami.jpg');
    $variantsData = [
        'original' => [
            'name' => 'Degan Jelly Original',
            'badge' => 'Original',
            'price' => $basePriceVal,
            'price_formatted' => 'Rp' . number_format($basePriceVal, 0, ',', '.'),
            'description' => 'Degan jelly kelapa muda asli pilihan dengan tekstur kenyal lembut alami tanpa bahan pengawet, disajikan murni langsung dalam batok kelapa segar.',
            'image' => $productSharedImage,
        ],
        'mix' => [
            'name' => 'Degan Jelly Mix',
            'badge' => 'Mix Buah',
            'price' => $basePriceVal,
            'price_formatted' => 'Rp' . number_format($basePriceVal, 0, ',', '.'),
            'description' => 'Perpaduan segar degan jelly kelapa muda murni dengan pilihan isian buah segar favorit Anda (1 hingga 3 buah) untuk sensasi rasa yang lebih kaya.',
            'image' => $productSharedImage,
        ],
    ];

    $currentVariantData = $variantsData[$selectedVariant] ?? $variantsData['original'];
@endphp

<div class="container py-4">
    <!-- Breadcrumb (Aturan 4 & 6: Menyesuaikan nama varian aktif) -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none" style="color: var(--qdj-green-dark);">Beranda</a></li>
            <li class="breadcrumb-item"><a href="{{ route('products.index') }}" class="text-decoration-none" style="color: var(--qdj-green-dark);">Katalog</a></li>
            <li class="breadcrumb-item active text-muted" aria-current="page" id="breadcrumbTitle">
                {{ $isOriginal ? $currentVariantData['name'] : $product->name }}
            </li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Kolom Kiri: Foto Besar & Kesegaran -->
        <div class="col-lg-5">
            <div class="position-sticky" style="top: 5.5rem;">
                <div class="position-relative overflow-hidden rounded-4 mb-3" style="max-height: 420px; background-color: var(--qdj-mint);">
                    @php
                        $initialImg = $isOriginal ? $currentVariantData['image'] : ($product->imageUrl() ?: asset('images/tentang-kami.jpg'));
                    @endphp
                    <!-- Foto produk dinamis sesuai varian (Aturan 4) -->
                    <img id="productMainImage"
                         src="{{ $initialImg }}"
                         alt="{{ $isOriginal ? $currentVariantData['name'] : $product->name }}"
                         class="w-100 h-100 rounded-4 shadow-sm"
                         style="object-fit: cover; min-height: 340px; transition: opacity 0.25s ease;">

                    <!-- Freshness Badge di atas foto (Aturan 6: Tetap sama) -->
                    @php
                        $shelfLife = $product->shelf_life_hours ?? 96;
                        $remaining = $currentBatch ? $currentBatch->remainingHours() : 78;
                        $producedAt = $currentBatch ? $currentBatch->produced_at : now()->subHours(18);
                        $status = $currentBatch ? $currentBatch->freshnessStatus() : 'Sangat Segar';
                        $percentage = min(100, max(0, round(($remaining / $shelfLife) * 100)));
                    @endphp
                    <div class="position-absolute top-0 start-0 m-3">
                        @if($status === 'Sangat Segar')
                            <span class="badge-qdj-fresh-very shadow-sm">🌿 {{ $status }}</span>
                        @elseif($status === 'Segar')
                            <span class="badge-qdj-fresh shadow-sm">🌱 {{ $status }}</span>
                        @elseif($status === 'Segera Dikonsumsi')
                            <span class="badge-qdj-fresh-warn shadow-sm">⏰ {{ $status }}</span>
                        @else
                            <span class="badge-qdj-expired shadow-sm">⚠️ {{ $status }}</span>
                        @endif
                    </div>
                </div>

                <!-- Keterangan Batok Kelapa (Aturan 6: Tetap sama) -->
                <div class="text-muted small text-center">
                    📸 100% batok kelapa muda asli Muncar, Banyuwangi
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Detail, Selector Varian, Indikator Kesegaran & Isian -->
        <div class="col-lg-7">
            <!-- Info Utama Produk -->
            <div class="mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge-qdj-available">{{ $product->category?->name ?? 'Degan Jelly' }}</span>
                    @if($isOriginal)
                        <!-- Badge Varian Dinamis (Aturan 4) -->
                        <span class="badge rounded-pill bg-light text-success border px-3 py-1 fw-semibold" id="productVariantBadge">
                            {{ $currentVariantData['badge'] }}
                        </span>
                    @elseif($isPaket)
                        <span class="badge rounded-pill bg-light text-dark border px-3 py-1">
                            🥥 Paket Isi {{ $packageBatok }} Batok Kelapa
                        </span>
                    @endif
                </div>

                <!-- Judul Produk Dinamis (Aturan 4) -->
                <h1 class="display-6 fw-bold mb-2 text-dark" id="productTitle">
                    {{ $isOriginal ? $currentVariantData['name'] : $product->name }}
                </h1>

                <!-- Harga Produk Dinamis (Aturan 4) -->
                <p class="lead fs-4 fw-bold mb-3" style="color: var(--qdj-green-dark);">
                    <span id="productPriceDisplay">{{ $isOriginal ? $currentVariantData['price_formatted'] : 'Rp' . number_format($product->price, 0, ',', '.') }}</span>
                    @if($isPaket)
                        <span class="fs-6 fw-normal text-muted">/ paket (isi {{ $packageBatok }} batok)</span>
                    @endif
                </p>

                <!-- Deskripsi Produk Dinamis (Aturan 4) -->
                <p class="text-muted" style="line-height: 1.7;" id="productDescription">
                    {{ $isOriginal ? $currentVariantData['description'] : ($product->description ?? 'Degan Jelly istimewa dengan tekstur kenyal lembut dari air kelapa murni, disajikan dingin menyegarkan di dalam batok kelapa asli.') }}
                </p>

                <!-- ========================================================================= -->
                <!-- ATURAN 1: Selector Varian (Original / Mix) Gaya Segmented Button / Pill    -->
                <!-- ========================================================================= -->
                @if($isOriginal)
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-0" style="letter-spacing: 0.5px;">
                                Pilih Varian Produk:
                            </label>
                            <span class="small text-muted" id="variantHintText">
                                {{ $selectedVariant === 'original' ? 'Murni tanpa isian' : 'Kombinasi 1 - 3 isian buah' }}
                            </span>
                        </div>

                        <div class="variant-segmented-container rounded-pill" role="tablist" aria-label="Pilihan Varian Produk">
                            <button type="button"
                                    id="btnVariantOriginal"
                                    class="variant-pill-btn flex-fill {{ $selectedVariant === 'original' ? 'active' : '' }}"
                                    data-variant="original">
                                🥥 Original
                            </button>
                            <button type="button"
                                    id="btnVariantMix"
                                    class="variant-pill-btn flex-fill {{ $selectedVariant === 'mix' ? 'active' : '' }}"
                                    data-variant="mix">
                                🍓 Mix (1 - 3 Buah)
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Form Pemesanan & Kustomisasi -->
            <form id="orderForm" method="POST" action="{{ route('cart.store', $product) }}">
                @csrf
                @if($isOriginal)
                    <!-- Hidden input varian untuk alur keranjang/checkout -->
                    <input type="hidden" name="varian" id="inputVariant" value="{{ $selectedVariant }}">
                @endif

                <!-- KARTU 1: Indikator Kesegaran (Aturan 6: Tetap sama untuk kedua varian) -->
                <div class="qdj-card p-4 mb-4" style="background: linear-gradient(180deg, #F3F9F5 0%, #FFFFFF 100%); border-color: var(--qdj-primary-border);">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size: 1.3rem;">⏱️</span>
                            <h2 class="h6 fw-bold mb-0 text-dark">Indikator Kesegaran</h2>
                        </div>
                        @if($status === 'Sangat Segar')
                            <span class="badge-qdj-fresh-very">{{ $status }}</span>
                        @elseif($status === 'Segar')
                            <span class="badge-qdj-fresh">{{ $status }}</span>
                        @elseif($status === 'Segera Dikonsumsi')
                            <span class="badge-qdj-fresh-warn">{{ $status }}</span>
                        @else
                            <span class="badge-qdj-expired">{{ $status }}</span>
                        @endif
                    </div>

                    <div class="row g-2 small text-muted my-2">
                        <div class="col-sm-6">
                            <span>Waktu produksi:</span>
                            <strong class="d-block text-dark">{{ $producedAt->format('d M Y, H:i') }} WIB</strong>
                        </div>
                        <div class="col-sm-6">
                            <span>Sisa masa simpan:</span>
                            <strong class="d-block text-dark">{{ $remaining }} jam dari {{ $shelfLife }} jam</strong>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="freshness-track my-2">
                        <div class="freshness-fill {{ $percentage > 50 ? 'freshness-fill-green' : ($percentage > 20 ? 'freshness-fill-yellow' : 'freshness-fill-red') }}"
                             style="width: {{ $percentage }}%;"></div>
                    </div>

                    <div class="text-muted small mt-2 fst-italic" style="font-size: 0.8rem;">
                        * Keterangan: perkiraan saat ini, dihitung ulang saat checkout.
                    </div>
                </div>

                <!-- KARTU 2: Logika Isian Berdasarkan Kategori & Varian -->
                @if($isRasa)
                    <!-- Kategori Rasa: Tanpa Tambahan Isian Buah -->
                    <div class="qdj-card p-4 mb-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span style="font-size: 1.3rem;">🥥</span>
                            <h2 class="h6 fw-bold mb-0 text-dark">Varian Rasa Khas</h2>
                        </div>
                        <p class="text-muted small mb-0">
                            Varian degan jelly rasa ini disajikan istimewa dengan aroma dan cita rasa autentik alami tanpa tambahan isian buah.
                        </p>
                    </div>
                @elseif($isPaket)
                    <!-- Kategori Paket: Kustomisasi Mix Tiap Batok -->
                    <div class="qdj-card p-4 mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span style="font-size: 1.3rem;">🥥</span>
                                <h2 class="h6 fw-bold mb-0 text-dark">Kustomisasi Mix Tiap Batok</h2>
                            </div>
                            <span class="badge rounded-pill bg-warning text-dark border small px-2 py-1">Paket {{ $packageBatok }} Batok</span>
                        </div>
                        <p class="text-muted small mb-3">
                            Paket ini berisi <strong>{{ $packageBatok }} batok kelapa</strong>. Silakan pilih kombinasi isian buah untuk masing-masing batok di bawah ini (maksimal 3 buah per batok) atau biarkan original murni tanpa isian.
                        </p>

                        <div id="batokMixContainer" class="d-flex flex-column gap-3">
                            @for($b = 1; $b <= $packageBatok; $b++)
                                <div class="batok-block p-3 rounded-4"
                                     id="batokBlock_{{ $b }}"
                                     data-batok="{{ $b }}"
                                     style="background: #FAF7F2; border: 1.5px solid var(--qdj-border);">
                                    <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fs-6">🥥</span>
                                            <strong class="text-dark">Batok Kelapa {{ $b }}</strong>
                                        </div>
                                        <span class="small text-muted batok-counter" id="batokCounter_{{ $b }}">0 / 3 dipilih</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($fruits as $fruit)
                                            @php($isOutOfStock = $fruit->stok_porsi < 1)
                                            <label class="fruit-chip batok-fruit-chip {{ $isOutOfStock ? 'disabled' : '' }}"
                                                   data-batok="{{ $b }}"
                                                   data-id="{{ $fruit->id }}"
                                                   data-price="{{ $fruit->harga_porsi }}"
                                                   data-name="{{ $fruit->nama_buah }}">
                                                <input type="checkbox"
                                                       name="batok_buah[{{ $b }}][]"
                                                       value="{{ $fruit->id }}"
                                                       class="d-none batok-checkbox"
                                                       data-batok="{{ $b }}"
                                                       {{ $isOutOfStock ? 'disabled' : '' }}>
                                                <span>{{ $fruit->nama_buah }}</span>
                                                @if($isOutOfStock)
                                                    <span class="badge bg-danger rounded-pill" style="font-size: 0.7rem;">Habis</span>
                                                @else
                                                    <span class="text-muted small">+Rp{{ number_format($fruit->harga_porsi, 0, ',', '.') }}</span>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                    <div id="batokAlert_{{ $b }}" class="alert alert-warning py-1 px-3 small rounded-pill mt-2 d-none" style="font-size: 0.78rem;">
                                        Maksimal 3 jenis buah untuk Batok {{ $b }} telah tercapai.
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>
                @else
                    <!-- ========================================================================= -->
                    <!-- ATURAN 2 & 3: KARTU PILIH ISIAN BUAH (Tampil saat Mix, Sembunyi saat Original) -->
                    <!-- ========================================================================= -->
                    <div class="qdj-card p-4 mb-4 {{ $selectedVariant === 'original' ? 'd-none' : '' }}" id="fruitCustomizationCard">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <span style="font-size: 1.3rem;">🍇</span>
                                <h2 class="h6 fw-bold mb-0 text-dark">Pilih Isian Buah</h2>
                            </div>
                            <span class="badge rounded-pill bg-success-subtle text-success border px-3 py-1 fw-bold" id="selectedCounter">
                                0 / 3 dipilih
                            </span>
                        </div>
                        <p class="text-muted small mb-3">
                            Pilih <strong>1 hingga maksimal 3 jenis buah segar</strong> untuk varian <strong>Degan Jelly Mix</strong> Anda.
                        </p>

                        <!-- Pilihan Fruit Chips -->
                        <div class="d-flex flex-wrap gap-2" id="fruitChipsContainer">
                            @foreach($fruits as $fruit)
                                @php($isOutOfStock = $fruit->stok_porsi < 1)
                                <label class="fruit-chip {{ $isOutOfStock ? 'disabled' : '' }}" data-id="{{ $fruit->id }}" data-price="{{ $fruit->harga_porsi }}" data-name="{{ $fruit->nama_buah }}">
                                    <input type="checkbox"
                                           name="buah[]"
                                           value="{{ $fruit->id }}"
                                           class="d-none fruit-checkbox"
                                           {{ $isOutOfStock ? 'disabled' : '' }}>
                                    <span>{{ $fruit->nama_buah }}</span>
                                    @if($isOutOfStock)
                                        <span class="badge bg-danger rounded-pill" style="font-size: 0.7rem;">Habis</span>
                                    @else
                                        <span class="text-muted small">+Rp{{ number_format($fruit->harga_porsi, 0, ',', '.') }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>

                        <!-- Pesan batas maksimal buah -->
                        <div id="fruitLimitAlert" class="alert alert-warning py-2 px-3 small rounded-pill mt-3 d-none">
                            Maksimal 3 jenis isian buah telah dipilih. Hapus centang salah satu jika ingin menukar buah lain.
                        </div>

                        <!-- Notice validasi mix buah -->
                        <div id="mixFruitNotice" class="mt-3 p-2 px-3 rounded-3 small bg-light border text-muted d-flex align-items-center gap-2">
                            <span id="mixNoticeIcon">ℹ️</span>
                            <span id="mixNoticeText">Silakan pilih minimal 1 buah (maksimal 3) untuk varian Mix.</span>
                        </div>
                    </div>
                @endif

                <!-- KARTU 3: Ringkasan Harga & Checkout -->
                <div class="qdj-card p-4">
                    <h2 class="h6 fw-bold mb-3 text-dark">Ringkasan Harga</h2>

                    <div class="d-flex justify-content-between py-2 border-bottom small">
                        <span class="text-muted">Harga dasar</span>
                        <strong class="text-dark" id="summaryBasePrice">
                            {{ $isOriginal ? $currentVariantData['price_formatted'] : 'Rp' . number_format($product->price, 0, ',', '.') }}
                        </strong>
                    </div>

                    @if(! $isRasa)
                        <div class="d-flex justify-content-between py-2 border-bottom small {{ ($isOriginal && $selectedVariant === 'original') ? 'd-none' : '' }}" id="summaryAddonRow">
                            <span class="text-muted">Tambahan isian</span>
                            <strong class="text-dark" id="displayAddonPrice">+Rp0</strong>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="fw-semibold">Harga satuan</span>
                        <strong class="fw-bold fs-6 text-dark" id="displayUnitPrice">
                            {{ $isOriginal ? $currentVariantData['price_formatted'] : 'Rp' . number_format($product->price, 0, ',', '.') }}
                        </strong>
                    </div>

                    <!-- Stepper Jumlah -->
                    <div class="d-flex align-items-center justify-content-between py-3">
                        <span class="small fw-semibold">{{ $isPaket ? 'Jumlah Paket:' : 'Jumlah Batok:' }}</span>
                        <div class="qdj-stepper">
                            <button type="button" class="qdj-stepper-btn" id="btnMinus">-</button>
                            <input type="number"
                                   name="quantity"
                                   id="inputQty"
                                   class="qdj-stepper-val"
                                   value="1"
                                   min="1"
                                   max="{{ $maxStock }}"
                                   readonly>
                            <button type="button" class="qdj-stepper-btn" id="btnPlus">+</button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top mb-4">
                        <span class="text-muted">Total Pembayaran:</span>
                        <span class="fs-4 fw-bold" style="color: var(--qdj-green-dark);" id="displayTotalPrice">
                            {{ $isOriginal ? $currentVariantData['price_formatted'] : 'Rp' . number_format($product->price, 0, ',', '.') }}
                        </span>
                    </div>

                    @if($product->isAvailable())
                        <div class="d-grid gap-2">
                            <!-- Tombol checkout: dikendalikan aktif/nonaktif oleh logika varian & validasi -->
                            <button type="submit" id="btnSubmitOrder" class="btn btn-qdj btn-lg py-3">
                                Lanjut ke Checkout
                            </button>
                        </div>
                    @else
                        <button type="button" class="btn btn-qdj-disabled w-100 py-3" disabled>
                            Stok Saat Ini Tidak Tersedia
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isRasa = {{ $isRasa ? 'true' : 'false' }};
    const isPaket = {{ $isPaket ? 'true' : 'false' }};
    const isOriginal = {{ $isOriginal ? 'true' : 'false' }};
    const maxFruits = 3;
    const maxStock = {{ $maxStock }};

    const inputQty = document.getElementById('inputQty');
    const btnMinus = document.getElementById('btnMinus');
    const btnPlus = document.getElementById('btnPlus');
    const displayAddon = document.getElementById('displayAddonPrice');
    const displayUnit = document.getElementById('displayUnitPrice');
    const displayTotal = document.getElementById('displayTotalPrice');
    const btnSubmitOrder = document.getElementById('btnSubmitOrder');

    function formatRupiah(num) {
        return 'Rp' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    if (isRasa) {
        const basePrice = {{ $product->price }};
        function updateRasa() {
            const qty = parseInt(inputQty.value) || 1;
            if (displayAddon) displayAddon.textContent = '+ Rp0';
            if (displayUnit) displayUnit.textContent = formatRupiah(basePrice);
            if (displayTotal) displayTotal.textContent = formatRupiah(basePrice * qty);
        }

        if (btnMinus && btnPlus && inputQty) {
            btnMinus.addEventListener('click', function () {
                let current = parseInt(inputQty.value) || 1;
                if (current > 1) {
                    inputQty.value = current - 1;
                    updateRasa();
                }
            });

            btnPlus.addEventListener('click', function () {
                let current = parseInt(inputQty.value) || 1;
                if (current < maxStock) {
                    inputQty.value = current + 1;
                    updateRasa();
                }
            });
        }

        updateRasa();
    } else if (isPaket) {
        const packageBatok = {{ $packageBatok }};
        const basePrice = {{ $product->price }};

        function updatePaketCalculations() {
            const qty = parseInt(inputQty.value) || 1;
            let onePackageAddons = 0;

            for (let b = 1; b <= packageBatok; b++) {
                const block = document.getElementById('batokBlock_' + b);
                if (! block) continue;
                const checkedChips = block.querySelectorAll('.batok-checkbox:checked');
                checkedChips.forEach(cb => {
                    const chip = cb.closest('.fruit-chip');
                    onePackageAddons += parseInt(chip?.getAttribute('data-price') || 0);
                });
            }

            const totalAddons = onePackageAddons * qty;
            if (displayAddon) displayAddon.textContent = '+ ' + formatRupiah(totalAddons);
            const total = (basePrice * qty) + totalAddons;
            if (displayUnit) displayUnit.textContent = formatRupiah(basePrice) + ' / paket (' + packageBatok + ' batok)';
            if (displayTotal) displayTotal.textContent = formatRupiah(total);
        }

        document.querySelectorAll('.batok-fruit-chip:not(.disabled)').forEach(chip => {
            chip.addEventListener('click', function (e) {
                const checkbox = this.querySelector('input[type="checkbox"]');
                if (! checkbox) return;
                const bNum = this.getAttribute('data-batok');
                const block = document.getElementById('batokBlock_' + bNum);
                const alertBox = document.getElementById('batokAlert_' + bNum);
                const counter = document.getElementById('batokCounter_' + bNum);
                const selectedInBatok = block ? block.querySelectorAll('.batok-checkbox:checked').length : 0;

                if (! checkbox.checked) {
                    if (selectedInBatok >= maxFruits) {
                        if (alertBox) alertBox.classList.remove('d-none');
                        return;
                    }
                    checkbox.checked = true;
                    this.classList.add('selected');
                    if (alertBox) alertBox.classList.add('d-none');
                } else {
                    checkbox.checked = false;
                    this.classList.remove('selected');
                    if (alertBox) alertBox.classList.add('d-none');
                }

                const newCount = block ? block.querySelectorAll('.batok-checkbox:checked').length : 0;
                if (counter) counter.textContent = `${newCount} / ${maxFruits} dipilih`;

                updatePaketCalculations();
            });
        });

        if (btnMinus && btnPlus && inputQty) {
            btnMinus.addEventListener('click', function () {
                let current = parseInt(inputQty.value) || 1;
                if (current > 1) {
                    inputQty.value = current - 1;
                    updatePaketCalculations();
                }
            });

            btnPlus.addEventListener('click', function () {
                let current = parseInt(inputQty.value) || 1;
                if (current < maxStock) {
                    inputQty.value = current + 1;
                    updatePaketCalculations();
                }
            });
        }

        updatePaketCalculations();
    } else if (isOriginal) {
        // =========================================================================
        // ATURAN 4: Data per varian disimpan dalam satu objek JavaScript agar mudah diedit
        // =========================================================================
        const variantsData = @json($variantsData);
        let activeVariant = '{{ $selectedVariant }}'; // Nilai awal: 'original' atau 'mix'
        let currentBasePrice = variantsData[activeVariant]?.price || {{ $product->price }};

        const inputVariant = document.getElementById('inputVariant');
        const fruitCard = document.getElementById('fruitCustomizationCard');
        const chips = document.querySelectorAll('.fruit-chip:not(.disabled)');
        const counter = document.getElementById('selectedCounter');
        const alertBox = document.getElementById('fruitLimitAlert');
        const mixNotice = document.getElementById('mixFruitNotice');
        const mixNoticeText = document.getElementById('mixNoticeText');
        const mixNoticeIcon = document.getElementById('mixNoticeIcon');
        const variantHintText = document.getElementById('variantHintText');
        const summaryAddonRow = document.getElementById('summaryAddonRow');

        const btnVarOriginal = document.getElementById('btnVariantOriginal');
        const btnVarMix = document.getElementById('btnVariantMix');

        // =========================================================================
        // ATURAN 5: Reset pilihan buah saat beralih ke varian Original
        // =========================================================================
        function resetFruitSelection() {
            chips.forEach(chip => {
                const cb = chip.querySelector('.fruit-checkbox');
                if (cb) cb.checked = false;
                chip.classList.remove('selected');
            });
            if (alertBox) alertBox.classList.add('d-none');
            if (counter) counter.textContent = `0 / ${maxFruits} dipilih`;
        }

        // =========================================================================
        // ATURAN 2 & 3: Logika Validasi Tombol Beli & Kalkulasi Harga
        // =========================================================================
        function updateOriginalMixCalculations() {
            let addonPrice = 0;
            let selectedCount = 0;

            if (activeVariant === 'mix') {
                // Hitung buah yang dipilih pada varian Mix
                chips.forEach(chip => {
                    const cb = chip.querySelector('.fruit-checkbox');
                    if (cb && cb.checked) {
                        selectedCount++;
                        addonPrice += parseInt(chip.getAttribute('data-price') || 0);
                    }
                });

                if (counter) {
                    counter.textContent = `${selectedCount} / ${maxFruits} dipilih`;
                }

                // Validasi: Minimal 1 buah dan maksimal 3 buah untuk mengaktifkan tombol beli
                if (btnSubmitOrder) {
                    if (selectedCount >= 1 && selectedCount <= maxFruits) {
                        btnSubmitOrder.disabled = false;
                        btnSubmitOrder.classList.remove('btn-qdj-disabled');
                        btnSubmitOrder.classList.add('btn-qdj');
                        btnSubmitOrder.textContent = 'Lanjut ke Checkout';

                        if (mixNotice) {
                            mixNotice.className = 'mt-3 p-2 px-3 rounded-3 small bg-success-subtle border border-success-subtle text-success d-flex align-items-center gap-2';
                            if (mixNoticeIcon) mixNoticeIcon.textContent = '✓';
                            if (mixNoticeText) mixNoticeText.textContent = `${selectedCount} buah terpilih! Siap melanjutkan pesanan.`;
                        }
                    } else {
                        btnSubmitOrder.disabled = true;
                        btnSubmitOrder.classList.remove('btn-qdj');
                        btnSubmitOrder.classList.add('btn-qdj-disabled');
                        btnSubmitOrder.textContent = 'Pilih Minimal 1 Buah untuk Lanjut';

                        if (mixNotice) {
                            mixNotice.className = 'mt-3 p-2 px-3 rounded-3 small bg-light border text-muted d-flex align-items-center gap-2';
                            if (mixNoticeIcon) mixNoticeIcon.textContent = 'ℹ️';
                            if (mixNoticeText) mixNoticeText.textContent = 'Silakan pilih minimal 1 buah (maksimal 3) untuk varian Mix.';
                        }
                    }
                }
            } else {
                // Aturan 2: Jika varian Original -> tombol beli langsung aktif tanpa validasi buah
                addonPrice = 0;
                if (btnSubmitOrder) {
                    btnSubmitOrder.disabled = false;
                    btnSubmitOrder.classList.remove('btn-qdj-disabled');
                    btnSubmitOrder.classList.add('btn-qdj');
                    btnSubmitOrder.textContent = 'Lanjut ke Checkout';
                }
            }

            if (displayAddon) displayAddon.textContent = '+ ' + formatRupiah(addonPrice);

            const unitPrice = currentBasePrice + addonPrice;
            if (displayUnit) displayUnit.textContent = formatRupiah(unitPrice);

            const qty = parseInt(inputQty.value) || 1;
            if (displayTotal) displayTotal.textContent = formatRupiah(unitPrice * qty);
        }

        // =========================================================================
        // ATURAN 1, 4, 7: Fungsi Switch Varian & Sync URL Parameter
        // =========================================================================
        function switchVariant(variant, updateUrl = true) {
            if (!variantsData[variant]) return;
            const previousVariant = activeVariant;
            activeVariant = variant;

            // Sinkronisasi hidden input form
            if (inputVariant) inputVariant.value = variant;

            // Aturan 5: Jika customer beralih dari Mix ke Original, reset pilihan buah
            if (previousVariant === 'mix' && variant === 'original') {
                resetFruitSelection();
            }

            // Tampilan segmented button
            if (variant === 'original') {
                btnVarOriginal?.classList.add('active');
                btnVarMix?.classList.remove('active');
                if (variantHintText) variantHintText.textContent = 'Murni tanpa isian';
            } else {
                btnVarMix?.classList.add('active');
                btnVarOriginal?.classList.remove('active');
                if (variantHintText) variantHintText.textContent = 'Kombinasi 1 - 3 isian buah';
            }

            // Aturan 4: Ubah data foto, judul, breadcrumb, badge, deskripsi, harga dari objek data
            const vData = variantsData[variant];
            currentBasePrice = vData.price;

            const mainImg = document.getElementById('productMainImage');
            if (mainImg && vData.image) {
                mainImg.src = vData.image;
                mainImg.alt = vData.name;
            }

            const titleEl = document.getElementById('productTitle');
            if (titleEl) titleEl.textContent = vData.name;

            const breadcrumbEl = document.getElementById('breadcrumbTitle');
            if (breadcrumbEl) breadcrumbEl.textContent = vData.name;

            document.title = `${vData.name} | Queen Degan Jelly`;

            const badgeEl = document.getElementById('productVariantBadge');
            if (badgeEl) badgeEl.textContent = vData.badge;

            const descEl = document.getElementById('productDescription');
            if (descEl) descEl.textContent = vData.description;

            const priceDisplayEl = document.getElementById('productPriceDisplay');
            if (priceDisplayEl) priceDisplayEl.textContent = vData.price_formatted;

            const summaryBaseEl = document.getElementById('summaryBasePrice');
            if (summaryBaseEl) summaryBaseEl.textContent = vData.price_formatted;

            // Aturan 2: Jika Original, sembunyikan kartu isian buah
            // Aturan 3: Jika Mix, tampilkan kartu isian buah
            if (fruitCard) {
                if (variant === 'original') {
                    fruitCard.classList.add('d-none');
                    if (summaryAddonRow) summaryAddonRow.classList.add('d-none');
                } else {
                    fruitCard.classList.remove('d-none');
                    if (summaryAddonRow) summaryAddonRow.classList.remove('d-none');
                }
            }

            // Aturan 7: Update parameter URL (?varian=...) agar dapat dishare/dibuka langsung
            if (updateUrl && window.history.replaceState) {
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.set('varian', variant);
                window.history.replaceState({}, '', currentUrl);
            }

            // Validasi & hitung ulang total
            updateOriginalMixCalculations();
        }

        // Listener klik tombol selector varian
        btnVarOriginal?.addEventListener('click', () => switchVariant('original', true));
        btnVarMix?.addEventListener('click', () => switchVariant('mix', true));

        // Handler interaksi checkbox buah pada varian Mix
        chips.forEach(chip => {
            chip.addEventListener('click', function (e) {
                if (activeVariant !== 'mix') return;

                const checkbox = this.querySelector('.fruit-checkbox');
                if (! checkbox) return;

                const checkedCount = document.querySelectorAll('.fruit-checkbox:checked').length;

                if (! checkbox.checked) {
                    if (checkedCount >= maxFruits) {
                        if (alertBox) alertBox.classList.remove('d-none');
                        return;
                    }
                    checkbox.checked = true;
                    this.classList.add('selected');
                    if (alertBox) alertBox.classList.add('d-none');
                } else {
                    checkbox.checked = false;
                    this.classList.remove('selected');
                    if (alertBox) alertBox.classList.add('d-none');
                }

                updateOriginalMixCalculations();
            });
        });

        // Handler stepper jumlah batok
        if (btnMinus && btnPlus && inputQty) {
            btnMinus.addEventListener('click', function () {
                let current = parseInt(inputQty.value) || 1;
                if (current > 1) {
                    inputQty.value = current - 1;
                    updateOriginalMixCalculations();
                }
            });

            btnPlus.addEventListener('click', function () {
                let current = parseInt(inputQty.value) || 1;
                if (current < maxStock) {
                    inputQty.value = current + 1;
                    updateOriginalMixCalculations();
                }
            });
        }

        // Aturan 7: Inisialisasi varian dari URL parameter atau default blade
        const urlParams = new URLSearchParams(window.location.search);
        const requestedVarian = urlParams.get('varian')?.toLowerCase();
        if (requestedVarian === 'mix') {
            switchVariant('mix', false);
        } else {
            switchVariant('original', false);
        }
    }
});
</script>
@endpush
