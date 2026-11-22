<!-- Top Navbar Toko (Pelanggan) -->
<header class="qdj-navbar sticky-top">
    <div class="container d-flex align-items-center justify-content-between px-3">
        <!-- Left: Hamburger (☰) + Brand Logo & Name -->
        <div class="d-flex align-items-center gap-2 gap-sm-3">
            <button class="qdj-hamburger-btn btn btn-link p-0 text-dark border-0" type="button" id="openDrawerBtn" aria-label="Buka Menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <a class="navbar-brand text-decoration-none m-0 d-flex align-items-center gap-2" href="{{ route('home') }}">
                <img src="{{ asset('images/logo.png') }}" alt="{{ $shopProfile?->nama_toko ?: 'Queen Degan Jelly' }}" class="qdj-brand-logo">
                <span class="brand-name-text">{{ $shopProfile?->nama_toko ?: 'Queen Degan Jelly' }}</span>
            </a>
        </div>

        <!-- Right: Beranda, Kontak, Keranjang, Masuk/Akun -->
        <div class="d-flex align-items-center gap-1 gap-sm-2">
            <!-- Beranda -->
            <a href="{{ route('home') }}" class="qdj-quick-nav-link {{ request()->routeIs('home') ? 'active' : '' }}" title="Beranda">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <span class="d-none d-sm-inline ms-1">Beranda</span>
            </a>

            <!-- Kontak -->
            <a href="#kontak" class="qdj-quick-nav-link" title="Kontak Kami">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                <span class="d-none d-sm-inline ms-1">Kontak</span>
            </a>

            <!-- Keranjang -->
            @php($cartCount = count(session('cart', [])))
            <a href="{{ route('cart.index') }}" class="qdj-quick-nav-link position-relative {{ request()->routeIs('cart.*') ? 'active' : '' }}" title="Keranjang Belanja">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                <span class="d-none d-sm-inline ms-1">Keranjang</span>
                @if($cartCount > 0)
                    <span class="badge rounded-pill cart-badge" style="background-color: var(--qdj-blue); color: #FFFFFF;">{{ $cartCount }}</span>
                @endif
            </a>

            <!-- Masuk / Akun Customer -->
            @auth
                <div class="dropdown d-inline-block">
                    <button class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle d-inline-flex align-items-center gap-1 px-2 px-sm-3" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="min-height: 44px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <span class="d-none d-sm-inline">{{ Str::words(auth()->user()->name, 1, '') }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><h6 class="dropdown-header">{{ auth()->user()->name }}</h6></li>
                        @if(auth()->user()->isAdmin())
                            <li><a class="dropdown-item small" href="{{ route('admin.dashboard') }}">🛡️ Panel Admin</a></li>
                        @else
                            <li><a class="dropdown-item small" href="{{ route('orders.index') }}">📦 Lihat Pesanan</a></li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item small text-danger">Keluar</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-sm btn-qdj rounded-pill d-inline-flex align-items-center gap-1 px-2 px-sm-3" title="Masuk Pelanggan" style="min-height: 44px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span class="d-none d-sm-inline">Masuk</span>
                </a>
            @endauth
        </div>
    </div>
</header>
