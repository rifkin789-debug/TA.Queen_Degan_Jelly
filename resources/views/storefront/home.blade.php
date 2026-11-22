@extends('layouts.app')

@section('title', 'Beranda | Queen Degan Jelly Muncar')

@section('content')
@php
    $phoneDisplay = $shopProfile?->nomor_hp ?: '+62 812-3456-7890';
    $cleanPhone = preg_replace('/[^0-9]/', '', $phoneDisplay);
    $waNumber = str_starts_with($cleanPhone, '0') ? ('62' . substr($cleanPhone, 1)) : $cleanPhone;
@endphp
<!-- ============================================================
     1. HERO SECTION DENGAN FOTO BLUR DI BELAKANG FONT
     ============================================================ -->
<section class="hero-mint">
    <!-- Latar Belakang Foto Kelapa Muda Asli dengan Efek Blur -->
    <div class="hero-blurred-bg" aria-hidden="true"></div>
    <!-- Lapisan Warm Ivory Overlay Transparan agar Teks Sangat Jelas & Nyaman Dibaca -->
    <div class="hero-blurred-overlay" aria-hidden="true"></div>

    <div class="container py-2">
        <span class="qdj-kicker px-3 py-1 bg-white bg-opacity-75 rounded-pill shadow-sm">✦ CITA RASA AUTENTIK MUNCAR, BANYUWANGI ✦</span>
        <h1 class="display-5 fw-bold mb-3" style="text-shadow: 0 1px 3px rgba(255, 255, 255, 0.9);">Kelezatan Alami Degan Jelly Murni</h1>
        <div class="qdj-divider"><span>◆</span></div>
        <p class="lead mb-4 fw-semibold" style="max-width: 680px; margin: 0 auto; color: #2B170E; text-shadow: 0 1px 2px rgba(255, 255, 255, 0.9);">
            Nikmati sensasi degan jelly kelapa muda asli tanpa bahan pengawet. Tekstur kenyal lembut disajikan langsung dalam batok kelapa segar pilihan.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="{{ route('products.index') }}" class="btn btn-qdj btn-lg px-4 shadow">
                Lihat Katalog Produk
            </a>
            <a href="{{ route('vouchers.purchase') }}" class="btn btn-qdj-outline btn-lg px-4 shadow-sm" style="background-color: #FFFFFF;">
                Beli Voucher Gift
            </a>
        </div>
    </div>
</section>

<!-- ============================================================
     2. JAMINAN MUTU & SERTIFIKASI (TRUST BADGES KHAS MONGGO)
     ============================================================ -->
<section class="container mb-5">
    <div class="d-flex flex-wrap justify-content-center gap-3 py-2">
        <div class="qdj-trust-pill">
            <span>🏅</span>
            <span>Halal Indonesia: ID 35110001512081222</span>
        </div>
        <div class="qdj-trust-pill">
            <span>🥥</span>
            <span>100% Kelapa Muda Muncar Asli</span>
        </div>
        <div class="qdj-trust-pill">
            <span>🍃</span>
            <span>Tanpa Bahan Pengawet & Pemanis Buatan</span>
        </div>
        <div class="qdj-trust-pill" style="border-color: var(--qdj-blue-border); background-color: var(--qdj-blue-soft);">
            <span>❄️</span>
            <span style="color: var(--qdj-blue-text);">Disajikan Segar & Dingin</span>
        </div>
    </div>
</section>

<!-- ============================================================
     3. NILAI & KEUNGGULAN UTAMA (4 BRAND COMMITMENTS)
     ============================================================ -->
<section class="container mb-5">
    <div class="text-center mb-4">
        <span class="qdj-kicker">KOMITMEN KUALITAS KAMI</span>
        <h2 class="h3 fw-bold text-dark">Standar Kemurnian Setiap Batok</h2>
        <div class="qdj-divider"><span>◆</span></div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="qdj-card p-4 text-center h-100">
                <div class="fs-1 mb-2">🥥</div>
                <h3 class="h5 fw-bold mb-2">100% Kelapa Muda</h3>
                <p class="text-muted small mb-0" style="line-height: 1.6;">
                    Dipetik langsung dari perkebunan pesisir Muncar, Banyuwangi dengan kematangan daging kelapa yang pas dan air kelapa segar berlimpah.
                </p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="qdj-card p-4 text-center h-100">
                <div class="fs-1 mb-2">🍃</div>
                <h3 class="h5 fw-bold mb-2">Tanpa Pengawet</h3>
                <p class="text-muted small mb-0" style="line-height: 1.6;">
                    Hanya menggunakan air kelapa murni, gula pasir alami, dan bubuk jelly berkualitas tinggi tanpa bahan kimia sintetis.
                </p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="qdj-card p-4 text-center h-100">
                <div class="fs-1 mb-2">🥣</div>
                <h3 class="h5 fw-bold mb-2">Tekstur Lembut Silky</h3>
                <p class="text-muted small mb-0" style="line-height: 1.6;">
                    Resep racikan khusus menghasilkan jelly kenyal lembut yang menyatu sempurna dengan gurihnya daging kelapa muda alami.
                </p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="qdj-card p-4 text-center h-100">
                <div class="fs-1 mb-2">✨</div>
                <h3 class="h5 fw-bold mb-2">Segar Setiap Hari</h3>
                <p class="text-muted small mb-0" style="line-height: 1.6;">
                    Diproduksi harian dengan kontrol mutu ketat serta indikator kesegaran batch transparan di setiap pesanan Anda.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     4. SECTION TENTANG KAMI (CERITA ARTISANAL KHAS TENTANG MONGGO)
     ============================================================ -->
<section class="container mb-5" id="tentang">
    <div class="qdj-card p-4 p-md-5">
        <div class="row g-4 g-lg-5 align-items-center">
            <!-- Foto Produk Asli di Kiri dengan Frame Mewah -->
            <div class="col-12 col-lg-5">
                <div class="qdj-image-frame position-relative">
                    <img src="{{ asset('images/tentang-kami.jpg') }}"
                         alt="Batok Degan Jelly Asli Queen Degan Jelly Muncar"
                         class="img-fluid w-100 d-block"
                         style="max-height: 340px; object-fit: cover; object-position: center;">
                    <div class="position-absolute bottom-0 start-0 m-3">
                        <span class="badge bg-white text-dark shadow-sm px-3 py-2 rounded-pill small fw-semibold" style="border: 1px solid var(--qdj-card-border);">
                            ✦ 100% Batok Kelapa Asli
                        </span>
                    </div>
                </div>
            </div>

            <!-- Teks Cerita Narasi di Kanan -->
            <div class="col-12 col-lg-7 ps-lg-4">
                <span class="qdj-kicker">FILOSOFI & DEDIKASI KAMI</span>
                <h2 class="h2 fw-bold mb-2 text-dark">Dari dapur rumahan di Muncar</h2>
                <div class="qdj-divider justify-content-start my-2"><span>◆</span></div>

                <p class="text-muted mb-3" style="line-height: 1.8; font-size: 1.02rem;">
                    Queen Degan Jelly adalah usaha kuliner asli milik Ibu Triana Wulandari di Muncar, Banyuwangi. Setiap batok diisi langsung dengan perpaduan air kelapa muda murni pilihan dan jelly lembut buatan sendiri tanpa tambahan bahan pengawet.
                </p>

                <p class="text-muted mb-3" style="line-height: 1.8; font-size: 0.98rem;">
                    Bermula dari kecintaan keluarga terhadap kesegaran pohon kelapa muda pesisir Banyuwangi, kami berkomitmen menghadirkan hidangan penutup alami yang lezat, menyejukkan dahaga, dan aman dikonsumsi anak-anak hingga orang tua.
                </p>

                <!-- Kutipan Filosofis Khas Monggo -->
                <div class="qdj-quote-box">
                    "Kelezatan sejati bermula dari kesegaran alami kelapa murni yang diracik sepenuh hati setiap hari."
                </div>

                <div class="pt-2">
                    <a href="{{ route('products.index') }}" class="btn btn-qdj px-4 py-2">
                        Jelajahi Katalog Produk
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     5. TAHAPAN PROSES PEMBUATAN (CRAFTING PROCESS KHAS MONGGO)
     ============================================================ -->
<section class="container mb-5">
    <div class="text-center mb-4">
        <span class="qdj-kicker">DEDIKASI DARI KEBUN KE MEJA ANDA</span>
        <h2 class="h3 fw-bold text-dark">4 Tahap Keaslian Queen Degan Jelly</h2>
        <div class="qdj-divider"><span>◆</span></div>
        <p class="text-muted mx-auto" style="max-width: 620px;">
            Setiap batok kelapa diproses secara teliti dan higienis demi menjaga kualitas nutrisi serta kesegaran alaminya.
        </p>
    </div>

    <div class="row g-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="qdj-step-card">
                <div class="qdj-step-num">01</div>
                <h3 class="h6 fw-bold mb-2 text-dark">Petik Subuh Hari</h3>
                <p class="text-muted small mb-0" style="line-height: 1.6;">
                    Kelapa muda terbaik dipanen setiap pagi dari pohon kelapa pesisir Muncar untuk memastikan kesegaran air dan kelembutan daging degan.
                </p>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="qdj-step-card">
                <div class="qdj-step-num">02</div>
                <h3 class="h6 fw-bold mb-2 text-dark">Penyaringan Murni</h3>
                <p class="text-muted small mb-0" style="line-height: 1.6;">
                    Air kelapa diekstraksi dan disaring higienis tanpa tambahan air biasa, menjaga kandungan elektrolit alami tetap utuh.
                </p>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="qdj-step-card">
                <div class="qdj-step-num">03</div>
                <h3 class="h6 fw-bold mb-2 text-dark">Racikan Jelly Lembut</h3>
                <p class="text-muted small mb-0" style="line-height: 1.6;">
                    Dimasak perlahan dengan takaran resep rahasia keluarga hingga menghasilkan tekstur jelly yang kenyal lembut dan lumer saat dimakan.
                </p>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="qdj-step-card">
                <div class="qdj-step-num">04</div>
                <h3 class="h6 fw-bold mb-2 text-dark">Saji Batok & Chiller</h3>
                <p class="text-muted small mb-0" style="line-height: 1.6;">
                    Dituangkan kembali ke dalam batok kelapa asli lalu didinginkan di chiller khusus pada suhu dingin 2°C–5°C siap dinikmati.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     6. LAYANAN & PENGALAMAN SPESIAL (SPECIAL SERVICES)
     ============================================================ -->
<section class="container mb-5">
    <div class="text-center mb-4">
        <span class="qdj-kicker">PENGALAMAN MENIKMATI DEGAN JELLY</span>
        <h2 class="h3 fw-bold text-dark">Layanan Istimewa untuk Anda</h2>
        <div class="qdj-divider"><span>◆</span></div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="qdj-card p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <span class="fs-2 mb-3 d-inline-block">🥥</span>
                    <h3 class="h5 fw-bold mb-2 text-dark">Pesan Harian Keluarga</h3>
                    <p class="text-muted small mb-3" style="line-height: 1.6;">
                        Nikmati degan jelly segar setiap hari langsung diantar ke rumah Anda di wilayah Muncar dan Banyuwangi sekitar.
                    </p>
                </div>
                <div>
                    <a href="{{ route('products.index') }}" class="btn btn-sm btn-qdj-outline w-100">
                        Beli Sekarang →
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="qdj-card p-4 h-100 d-flex flex-column justify-content-between" style="border: 1.5px solid var(--qdj-blue);">
                <div>
                    <span class="fs-2 mb-3 d-inline-block">🎁</span>
                    <span class="badge-qdj-blue mb-2">Populer untuk Hadiah</span>
                    <h3 class="h5 fw-bold mb-2 text-dark">Voucher Gift Kustomisasi</h3>
                    <p class="text-muted small mb-3" style="line-height: 1.6;">
                        Kirimkan voucher kejutan untuk teman atau keluarga. Penerima bebas menukarkan dan memilih varian serta topping buah favoritnya.
                    </p>
                </div>
                <div>
                    <a href="{{ route('vouchers.purchase') }}" class="btn btn-sm btn-qdj-blue w-100">
                        Beli Voucher Gift →
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     7. PRODUK PILIHAN
     ============================================================ -->
<section class="container pb-5 mb-4">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <span class="qdj-kicker">VARIAN FAVORIT</span>
            <h2 class="h3 fw-bold mb-0 text-dark">Produk Pilihan</h2>
            <p class="text-muted small mb-0">Varian degan jelly terfavorit untuk Anda dan keluarga</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-sm btn-qdj-outline">
            Lihat Semua →
        </a>
    </div>

    <div class="row g-4">
        @forelse($products as $product)
            @php($available = $product->isAvailable())
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="qdj-card h-100 d-flex flex-column p-3">
                    <div class="position-relative mb-3 overflow-hidden rounded-4" style="height: 190px; background-color: var(--qdj-mint);">
                        @if($product->imageUrl())
                            <img src="{{ $product->imageUrl() }}"
                                 alt="{{ $product->name }}"
                                 class="w-100 h-100"
                                 style="object-fit: cover;">
                        @else
                            <img src="{{ asset('images/tentang-kami.jpg') }}"
                                 alt="{{ $product->name }}"
                                 class="w-100 h-100"
                                 style="object-fit: cover;">
                        @endif

                        <div class="position-absolute top-0 end-0 m-2">
                            @if($available)
                                <span class="badge-qdj-available">Tersedia</span>
                            @else
                                <span class="badge-qdj-out">Habis</span>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-grow-1">
                        <h3 class="h6 fw-bold mb-1 text-dark">{{ $product->name }}</h3>
                        <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.85rem; line-height: 1.5;">
                            {{ Str::limit($product->description ?? 'Degan jelly manis alami dalam batok kelapa muda asli.', 60) }}
                        </p>

                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <span class="fw-bold fs-6" style="color: var(--qdj-brown-primary);">
                                Rp{{ number_format($product->price, 0, ',', '.') }}
                            </span>

                            @if($available)
                                <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-qdj px-3">
                                    Detail
                                </a>
                            @else
                                <button type="button" class="btn btn-sm btn-qdj-disabled px-3" disabled>
                                    Habis
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-4">
                <p class="text-muted">Belum ada produk aktif.</p>
            </div>
        @endforelse
    </div>
</section>

<!-- ============================================================
     8. MEDIA EKPOSE, INSTAGRAM REEL & TIKTOK
     ============================================================ -->
<section class="container mb-5">
    <div class="text-center mb-4">
        <span class="qdj-kicker">✦ SOROTAN PUBLIKASI & MEDIA SOSIAL ✦</span>
        <h2 class="h3 fw-bold text-dark">Liputan Media, Video Instagram & TikTok</h2>
        <div class="qdj-divider"><span>◆</span></div>
        <p class="text-muted small mx-auto" style="max-width: 680px;">
            Simak liputan khusus yang mengekspos produk Queen Degan Jelly serta saksikan video keseruan kami di Instagram Reel dan TikTok.
        </p>
    </div>

    <div class="row g-4 align-items-stretch">
        <!-- Kolom 1: Web yang mengekspos produk -->
        <div class="col-12 col-lg-4">
            <div class="qdj-card p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge-qdj-available">📰 Liputan Media</span>
                        <span class="text-muted small">Publikasi Resmi</span>
                    </div>

                    <h3 class="h5 fw-bold text-dark mb-2">
                        Mengenal Inovasi Degan Jelly Khas Muncar
                    </h3>

                    <p class="text-muted small mb-3" style="line-height: 1.7;">
                        Produk Queen Degan Jelly diliput sebagai kuliner khas pesisir Muncar, Banyuwangi. Mengangkat keaslian batok kelapa muda alami dengan kelembutan jelly murni tanpa pengawet.
                    </p>

                    <div class="p-3 rounded-4 mb-3" style="background-color: var(--qdj-mint-soft); border: 1px solid var(--qdj-card-border);">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-4">🌐</span>
                            <div class="small">
                                <strong class="text-dark d-block">Sorotan Google & Berita</strong>
                                <span class="text-muted" style="font-size: 0.8rem;">Ulasan profil, rasa & proses dapur.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="https://share.google/40foNahM5nWRq534q"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="btn btn-qdj w-100 py-2 d-inline-flex align-items-center justify-content-center gap-2">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        <span>Buka Liputan Media</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Kolom 2: Video Instagram Reel Resmi -->
        <div class="col-12 col-lg-4">
            <div class="qdj-card p-4 h-100 d-flex flex-column justify-content-between" style="border: 1.5px solid rgba(225, 48, 108, 0.4);">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge-qdj-available" style="background-color: #FDF2F8; color: #BE185D; border-color: #FBCFE8;">
                            📸 Instagram Reel
                        </span>
                        <span class="text-muted small">Video Resmi</span>
                    </div>

                    <h3 class="h5 fw-bold text-dark mb-2">
                        Video Reel Pembuatan & Penyajian
                    </h3>

                    <p class="text-muted small mb-3" style="line-height: 1.7;">
                        Saksikan video cuplikan pembuatan dan kelembutan degan jelly batok kelapa muda asli Queen Degan Jelly langsung di Instagram Reel kami.
                    </p>

                    <div class="position-relative overflow-hidden rounded-4 mb-3 p-3 text-center text-white shadow-sm"
                         style="background: linear-gradient(135deg, #405DE6 0%, #833AB4 45%, #E1306C 75%, #F77737 100%);">
                        <div class="mb-1">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="d-inline-block">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                            </svg>
                        </div>
                        <div class="fw-bold small mb-1">Queen Degan Jelly</div>
                        <div class="d-inline-flex align-items-center gap-1 bg-black bg-opacity-40 px-2 py-0.5 rounded-pill" style="font-size: 0.72rem;">
                            <span>▶ Putar di Instagram</span>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="https://www.instagram.com/reel/Clm5l7sAkWg/?bqrl=MWs0NGY5eWczeGlzNw=="
                       target="_blank"
                       rel="noopener noreferrer"
                       class="btn w-100 py-2 d-inline-flex align-items-center justify-content-center gap-2 text-white"
                       style="background: linear-gradient(45deg, #405DE6, #833AB4, #C13584, #E1306C, #FD1D1D); border-radius: 9999px; font-weight: 600; border: none; box-shadow: 0 4px 12px rgba(225, 48, 108, 0.25);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                        <span>Tonton Instagram Reel</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Kolom 3: Media yang Diliput di TikTok -->
        <div class="col-12 col-lg-4">
            <div class="qdj-card p-4 h-100 d-flex flex-column justify-content-between" style="border: 1.5px solid var(--qdj-gold);">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge-qdj-available" style="background-color: #FEF3C7; color: #92400E; border-color: #FCD34D;">
                            🎵 Liputan di TikTok
                        </span>
                        <span class="text-muted small">Viral di TikTok</span>
                    </div>

                    <h3 class="h5 fw-bold text-dark mb-2">
                        Liputan & Ulasan Viral di TikTok
                    </h3>

                    <p class="text-muted small mb-3" style="line-height: 1.7;">
                        Tonton video liputan dan ulasan langsung di TikTok yang mengekspos sensasi kelezatan dan kesegaran batok kelapa muda Queen Degan Jelly.
                    </p>

                    <div class="position-relative overflow-hidden rounded-4 mb-3 p-3 text-center text-white shadow-sm"
                         style="background: linear-gradient(135deg, #121212 0%, #2A170E 100%); border: 1px solid rgba(197, 160, 89, 0.5);">
                        <div class="mb-1">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="#FFFFFF" class="d-inline-block">
                                <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64c.29 0 .58.04.86.11V9.42a6.33 6.33 0 0 0-.86-.06 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.75a8.16 8.16 0 0 0 4.77 1.52V6.82a4.85 4.85 0 0 1-1-.13z"/>
                            </svg>
                        </div>
                        <div class="fw-bold small mb-1">Queen Degan Jelly</div>
                        <div class="d-inline-flex align-items-center gap-1 bg-white bg-opacity-20 px-2 py-0.5 rounded-pill" style="font-size: 0.72rem;">
                            <span>▶ Putar Liputan di TikTok</span>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="https://vt.tiktok.com/ZSpKBRuxg/"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="btn btn-dark w-100 py-2 d-inline-flex align-items-center justify-content-center gap-2"
                       style="background-color: #000000; border-color: #000000; border-radius: 9999px; font-weight: 600; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64c.29 0 .58.04.86.11V9.42a6.33 6.33 0 0 0-.86-.06 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.75a8.16 8.16 0 0 0 4.77 1.52V6.82a4.85 4.85 0 0 1-1-.13z"/>
                        </svg>
                        <span>Tonton Liputan di TikTok</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="container mb-5" style="max-width: 860px;">
    <div class="text-center mb-4">
        <span class="qdj-kicker">PERTANYAAN UMUM</span>
        <h2 class="h3 fw-bold text-dark">Seputar Queen Degan Jelly</h2>
        <div class="qdj-divider"><span>◆</span></div>
        <p class="text-muted small">Temukan jawaban atas pertanyaan seputar pemesanan, daya tahan, dan kualitas degan jelly kami.</p>
    </div>

    <div class="accordion qdj-accordion" id="qdjFaqAccordion">
        <div class="accordion-item">
            <h3 class="accordion-header" id="faqHeading1">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1">
                    Berapa lama daya tahan Queen Degan Jelly di dalam kulkas?
                </button>
            </h3>
            <div id="faqCollapse1" class="accordion-collapse collapse show" aria-labelledby="faqHeading1" data-bs-parent="#qdjFaqAccordion">
                <div class="accordion-body">
                    Karena dibuat dari 100% air kelapa muda murni tanpa pengawet buatan, Queen Degan Jelly paling nikmat dikonsumsi dalam keadaan dingin segera setelah diterima, atau dapat bertahan hingga 3–4 hari jika disimpan rapat di dalam kulkas/chiller bersuhu 2°C–5°C.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h3 class="accordion-header" id="faqHeading2">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                    Apakah degan jelly ini menggunakan campuran air biasa atau pemanis buatan?
                </button>
            </h3>
            <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#qdjFaqAccordion">
                <div class="accordion-body">
                    Sama sekali tidak. Kami berkomitmen menyajikan rasa manis alami yang murni dari air degan kelapa muda pilihan Muncar dengan gula pasir asli secukupnya dan bubuk jelly bersertifikat Halal, tanpa pemanis buatan maupun zat pengawet.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h3 class="accordion-header" id="faqHeading3">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                    Bagaimana cara kerja dan penukaran Voucher Gift?
                </button>
            </h3>
            <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#qdjFaqAccordion">
                <div class="accordion-body">
                    Anda cukup memilih paket voucher di menu <strong>Beli Voucher Gift</strong> dan menyelesaikan pembayaran via QRIS. Setelah terverifikasi, Anda akan mendapatkan kode voucher unik yang dapat dibagikan kepada penerima hadiah. Penerima cukup membuka menu <strong>Tukar Voucher</strong>, memasukkan kode tersebut, dan memilih varian produk serta isian buah favorit tanpa biaya tambahan.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h3 class="accordion-header" id="faqHeading4">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4" aria-expanded="false" aria-controls="faqCollapse4">
                    Apakah bisa memesan dalam jumlah besar untuk acara hajatan atau arisan?
                </button>
            </h3>
            <div id="faqCollapse4" class="accordion-collapse collapse" aria-labelledby="faqHeading4" data-bs-parent="#qdjFaqAccordion">
                <div class="accordion-body">
                    Tentu saja! Kami sering melayani pesanan hantaran dan konsumsi acara hajatan, arisan, syukuran, serta kegiatan kantor. Kami menyarankan pemesanan dilakukan minimal H-1 atau H-2 agar kami dapat menyiapkan batok kelapa muda segar dalam jumlah yang Anda butuhkan.
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     10. CALL TO ACTION BANNER (RESERVASI & PESAN CEPAT)
     ============================================================ -->
<section class="container mb-5">
    <div class="qdj-cta-banner text-center p-4 p-md-5">
        <span class="qdj-kicker text-warning">✦ KESEGARAN MURNI SETIAP HARI ✦</span>
        <h2 class="display-6 fw-bold mb-3 text-white">Siap Menikmati Kesegaran Alami Hari Ini?</h2>
        <p class="lead mb-4 text-white-50 mx-auto" style="max-width: 650px;">
            Dibuat segar setiap hari di dapur rumahan Muncar, Banyuwangi. Pesan online sekarang atau hubungi kami langsung via WhatsApp untuk pertanyaan pesanan Anda.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="{{ route('products.index') }}" class="btn btn-warning text-dark fw-bold rounded-pill px-4 py-2">
                Jelajahi Katalog Produk
            </a>
            <a href="https://wa.me/{{ $waNumber }}?text=Halo%20Queen%20Degan%20Jelly,%20saya%20ingin%20memesan%20degan%20jelly" target="_blank" rel="noopener" class="btn btn-outline-light rounded-pill px-4 py-2">
                Hubungi via WhatsApp
            </a>
        </div>
    </div>
</section>
@endsection
