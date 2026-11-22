<!-- Top Navbar Khusus Panel Admin -->
<header class="qdj-navbar sticky-top">
    <div class="container-fluid px-lg-4 d-flex align-items-center justify-content-between">
        <!-- Left: (Mobile Only Sidebar Toggle) + Brand Logo & Name -->
        <div class="d-flex align-items-center gap-2">
            <!-- Tombol Hamburger HANYA muncul di layar kecil (< 992px) untuk toggle sidebar -->
            <button class="btn btn-link p-0 text-dark border-0 d-lg-none me-2"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#adminSidebarCollapse"
                    aria-expanded="false"
                    aria-controls="adminSidebarCollapse"
                    aria-label="Buka Menu Sidebar">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <!-- Brand Logo & Nama (Tanpa hamburger di desktop) -->
            <a class="navbar-brand text-decoration-none m-0 d-flex align-items-center gap-2" href="{{ route('admin.dashboard') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Queen Degan Jelly" class="qdj-brand-logo">
                <span class="fw-bold brand-name-text" style="font-size: 1.15rem; color: var(--qdj-green-dark);">Queen Degan Jelly</span>
                <span class="badge rounded-pill bg-light text-secondary border px-2 py-1 ms-1 d-none d-sm-inline-block" style="font-size: 0.72rem; letter-spacing: 0.03em;">
                    Panel Admin
                </span>
            </a>
        </div>

        <!-- Right: Dropdown Akun Admin Saja (Tanpa Beranda, Kontak, & Keranjang) -->
        <div class="d-flex align-items-center">
            @auth
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle d-inline-flex align-items-center gap-2 px-3"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            style="min-height: 44px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span class="fw-semibold">{{ Str::words(auth()->user()->name, 1, '') }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><h6 class="dropdown-header">{{ auth()->user()->name }} (Admin)</h6></li>
                        <li><a class="dropdown-item small" href="{{ route('admin.dashboard') }}">🛡️ Dasbor Admin</a></li>
                        <li><a class="dropdown-item small" href="{{ route('admin.produk.index') }}">📦 Kelola Produk</a></li>
                        <li><a class="dropdown-item small" href="{{ route('admin.payments.index') }}">💳 Verifikasi Pembayaran</a></li>
                        <li><a class="dropdown-item small" href="{{ route('admin.business-profile.edit') }}">⚙️ Pengaturan Profil Usaha</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item small text-danger">Keluar</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endauth
        </div>
    </div>
</header>
