<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Queen Degan Jelly')</title>
    <!-- Google Fonts: Playfair Display (Serif Elegan) & Plus Jakarta Sans (Modern Clean Body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Queen Degan Jelly Design Tokens & Styling -->
    <link href="{{ asset('css/queen-degan.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @stack('styles')
</head>
<body>
    <!-- Top Navbar -->
    @section('navbar')
        @if(request()->routeIs('admin.*') && !request()->routeIs('admin.login'))
            @include('layouts.partials.admin-navbar')
        @else
            @include('layouts.partials.storefront-navbar')
        @endif
    @show

    <!-- Drawer Popup Menu (☰) -->
    @section('drawer')
        @if(!request()->routeIs('admin.*') || request()->routeIs('admin.login'))
            <div id="qdjDrawerBackdrop" class="qdj-drawer-backdrop" aria-hidden="true"></div>
            <aside id="qdjDrawer" class="qdj-drawer" role="dialog" aria-modal="true" aria-label="Menu Navigasi">
                <div class="qdj-drawer-header d-flex align-items-center justify-content-between p-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <img src="{{ asset('images/logo.png') }}" alt="Queen Degan Jelly" class="rounded-circle border" style="width: 32px; height: 32px; object-fit: cover;">
                        <span class="fw-bold text-dark fs-6">Queen Degan Jelly</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle d-flex align-items-center justify-content-center" id="closeDrawerBtn" aria-label="Tutup Menu" style="width: 44px; height: 44px;">
                        ✕
                    </button>
                </div>
                <div class="qdj-drawer-body p-3 overflow-y-auto flex-grow-1">
                    <nav class="d-flex flex-column gap-1" aria-label="Menu Lengkap">
                        <a class="qdj-drawer-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                            Beranda
                        </a>
                        <a class="qdj-drawer-link" href="{{ route('home') }}#tentang" id="drawerAboutLink">
                            Tentang Kami
                        </a>
                        <a class="qdj-drawer-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                            Katalog
                        </a>
                        <a class="qdj-drawer-link {{ request()->routeIs('vouchers.purchase*') ? 'active' : '' }}" href="{{ route('vouchers.purchase') }}">
                            Voucher Gift
                        </a>
                        <a class="qdj-drawer-link {{ request()->routeIs('vouchers.redeem*') ? 'active' : '' }}" href="{{ route('vouchers.redeem.enter') }}">
                            Tukar Voucher
                        </a>
                        <a class="qdj-drawer-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ auth()->check() ? route('orders.index') : route('orders.track') }}">
                            Lihat Pesanan
                        </a>
                        <a class="qdj-drawer-link" href="#kontak" id="drawerContactLink">
                            Kontak
                        </a>
                    </nav>

                    <div class="mt-4 pt-3 border-top small text-muted">
                        <p class="mb-1 fw-semibold text-dark">Dapur Rumahan Muncar</p>
                        <p class="mb-0">Kelapa muda asli dan jelly lembut tanpa pengawet.</p>
                    </div>
                </div>
            </aside>
        @endif
    @show

    <!-- Main Content -->
    <main class="flex-grow-1">
        @if(session('success'))
            <div class="container mt-3">
                <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="container mt-3">
                <div class="alert alert-danger alert-dismissible fade show rounded-4" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="container mt-3">
                <div class="alert alert-danger alert-dismissible fade show rounded-4" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    @section('footer')
        @if(!request()->routeIs('admin.*'))
            <footer class="qdj-footer" id="kontak">
                <div class="container">
                    <div class="row g-4 align-items-start">
                        <!-- Col 1: Brand & Tagline -->
                        <div class="col-lg-4 col-md-6">
                            <h3 class="h4 fw-bold text-white mb-2">{{ $shopProfile?->nama_toko ?: 'Queen Degan Jelly' }}</h3>
                            <p class="text-white-50 mb-3" style="line-height: 1.6;">
                                Segar alami dari kelapa muda.<br>
                                Tanpa pengawet, dibuat dengan cinta.
                            </p>
                            <p class="text-white-50 small mb-0" style="line-height: 1.6;">
                                UMKM kuliner khas dari Muncar, Banyuwangi. Menghadirkan degan jelly asli dalam batok kelapa segar berkualitas.
                            </p>
                        </div>

                        <!-- Col 2: Kontak -->
                        <div class="col-lg-4 col-md-6">
                            <h4 class="h5 fw-bold text-white mb-3">Kontak</h4>
                            <ul class="list-unstyled text-white-50 mb-0 d-flex flex-column gap-2">
                                <li>
                                    <strong class="text-white d-block small">WhatsApp:</strong>
                                    @php
                                        $phoneDisplay = $shopProfile?->nomor_hp ?: '+62 812-3456-7890';
                                        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneDisplay);
                                        if (str_starts_with($cleanPhone, '0')) {
                                            $waNumber = '62' . substr($cleanPhone, 1);
                                        } elseif (str_starts_with($cleanPhone, '62')) {
                                            $waNumber = $cleanPhone;
                                        } else {
                                            $waNumber = '62' . $cleanPhone;
                                        }
                                    @endphp
                                    <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener noreferrer" class="qdj-footer-wa-link">
                                        {{ $phoneDisplay }}
                                    </a>
                                </li>
                                <li>
                                    <strong class="text-white d-block small">Alamat Dapur:</strong>
                                    <span>{!! nl2br(e($shopProfile?->alamat ?: "Muncar, Banyuwangi\nJawa Timur, Indonesia")) !!}</span>
                                </li>
                                <li>
                                    <strong class="text-white d-block small">Jam Operasional:</strong>
                                    @php
                                        $jamOp = $shopProfile?->jam_operasional;
                                        if (! $jamOp || str_contains(strtolower($jamOp), 'setiap hari')) {
                                            $jamOp = 'Senin - Sabtu: 08:00 - 17:00 WIB';
                                        }
                                    @endphp
                                    <span>{{ $jamOp }}</span>
                                    @if(! str_contains(strtolower($jamOp), 'minggu') || ! str_contains(strtolower($jamOp), 'libur'))
                                        <span class="d-block small text-warning mt-1">Minggu Libur</span>
                                    @endif
                                </li>
                            </ul>
                        </div>

                        <!-- Col 3: Lokasi Kami -->
                        <div class="col-lg-4 col-md-12">
                            <h4 class="h5 fw-bold text-white mb-3">Lokasi Kami</h4>
                            <a href="https://maps.app.goo.gl/EPuG3ZhMEgdyooC66"
                               target="_blank"
                               rel="noopener"
                               class="d-block overflow-hidden position-relative shadow-sm mb-2"
                               style="border-radius: 12px; height: 190px; border: 1.5px solid rgba(255, 255, 255, 0.15);"
                               title="Buka Queen Degan Jelly di Google Maps">
                                <img src="{{ asset('images/peta-lokasi.jpg') }}"
                                     alt="Peta Lokasi Queen Degan Jelly di Google Maps"
                                     class="w-100 h-100 d-block"
                                     style="object-fit: cover; object-position: center;"
                                     loading="lazy">
                            </a>
                            <div class="mt-2">
                                <a href="https://maps.app.goo.gl/EPuG3ZhMEgdyooC66"
                                   target="_blank"
                                   rel="noopener"
                                   class="btn-footer-map">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    Buka di Google Maps
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Copyright -->
                    <div class="qdj-footer-bottom">
                        © {{ date('Y') }} {{ $shopProfile?->nama_toko ?: 'Queen Degan Jelly' }}. All rights reserved.
                    </div>
                </div>
            </footer>
        @endif
    @show

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const drawer = document.getElementById('qdjDrawer');
            const backdrop = document.getElementById('qdjDrawerBackdrop');
            const openBtn = document.getElementById('openDrawerBtn');
            const closeBtn = document.getElementById('closeDrawerBtn');
            const drawerAboutLink = document.getElementById('drawerAboutLink');
            const drawerContactLink = document.getElementById('drawerContactLink');

            function openDrawer() {
                if (!drawer) return;
                drawer.classList.add('show');
                backdrop.classList.add('show');
                document.body.style.overflow = 'hidden';
            }

            function closeDrawer() {
                if (!drawer) return;
                drawer.classList.remove('show');
                backdrop.classList.remove('show');
                document.body.style.overflow = '';
            }

            if (openBtn) openBtn.addEventListener('click', openDrawer);
            if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
            if (backdrop) backdrop.addEventListener('click', closeDrawer);
            if (drawerAboutLink) drawerAboutLink.addEventListener('click', closeDrawer);
            if (drawerContactLink) drawerContactLink.addEventListener('click', closeDrawer);

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && drawer && drawer.classList.contains('show')) {
                    closeDrawer();
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
