@extends('layouts.app')

@section('navbar')
    @include('layouts.partials.admin-navbar')
@endsection

@section('drawer')
@endsection

@section('footer')
@endsection

@section('content')
<div class="container-fluid px-lg-4 py-4">
    <div class="row g-4 align-items-start">
        <!-- Sidebar Hijau Tua -->
        <aside class="col-lg-3 col-xl-2 collapse d-lg-block mb-4 mb-lg-0" id="adminSidebarCollapse">
            <div class="admin-sidebar shadow-sm">
                <div class="px-2 pt-1 pb-3 mb-2 border-bottom border-white-50 d-flex align-items-center gap-2">
                    <img src="{{ asset('images/logo.png') }}" alt="Queen Degan Jelly" class="rounded-circle border border-white" style="width: 38px; height: 38px; object-fit: cover;">
                    <div>
                        <div class="brand-title" style="font-size: 0.95rem; line-height: 1.2;">Queen Degan Jelly</div>
                        <div class="small text-white-50" style="font-size: 0.75rem;">Panel Admin</div>
                    </div>
                </div>

                <nav class="d-flex flex-column" aria-label="Navigasi Admin">
                    <a class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                       href="{{ route('admin.dashboard') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        <span>Dasbor</span>
                    </a>

                    <a class="admin-nav-item {{ request()->routeIs('admin.produk.*') ? 'active' : '' }}"
                       href="{{ route('admin.produk.index') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                        <span>Produk & Isian</span>
                    </a>

                    <a class="admin-nav-item {{ request()->routeIs('admin.batches.*') ? 'active' : '' }}"
                       href="{{ route('admin.batches.index') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        <span>Batch Produksi</span>
                    </a>

                    <a class="admin-nav-item {{ request()->routeIs('admin.buah.*') ? 'active' : '' }}"
                       href="{{ route('admin.buah.index') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                        <span>Kelola Isian Buah</span>
                    </a>

                    @php($sidebarPendingCount = \App\Models\Payment::where('transaction_status', 'pending_verification')->count())
                    <a class="admin-nav-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}"
                       href="{{ route('admin.payments.index') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        <span>Pembayaran</span>
                        @if($sidebarPendingCount > 0)
                            <span class="badge bg-warning text-dark rounded-pill ms-auto px-2" style="font-size: 0.75rem;">
                                {{ $sidebarPendingCount }}
                            </span>
                        @endif
                    </a>

                    <a class="admin-nav-item {{ request()->routeIs('admin.business-profile.*') ? 'active' : '' }}"
                       href="{{ route('admin.business-profile.edit') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        <span>Pengaturan Profil</span>
                    </a>

                    <a class="admin-nav-item {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"
                       href="{{ route('admin.orders.index') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        <span>Pesanan</span>
                    </a>

                    <a class="admin-nav-item {{ request()->routeIs('admin.vouchers.*') || request()->routeIs('admin.voucher-payments.*') ? 'active' : '' }}"
                       href="{{ route('admin.vouchers.index') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>
                        <span>Kelola Voucher</span>
                    </a>

                    <hr class="border-white-50 my-3">

                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="admin-nav-item w-100 text-start border-0 bg-transparent text-danger">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                </nav>
            </div>
        </aside>

        <!-- Main Admin Content -->
        <main class="col-lg-9 col-xl-10">
            <!-- Header Putih: Halo, Admin -->
            <div class="admin-header shadow-sm">
                <div>
                    <span class="text-muted small d-block">Ruang Kerja Pengelola</span>
                    <h2 class="h5 fw-bold mb-0 text-dark">Halo, {{ auth()->user()->name ?? 'Admin' }}</h2>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a class="btn btn-sm btn-qdj-outline d-inline-flex align-items-center gap-1 px-3 py-1.5" href="{{ route('home') }}" target="_blank" title="Buka Toko di Tab Baru">
                        <span>Lihat Toko</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Page Specific Content -->
            @yield('admin-content')
        </main>
    </div>
</div>
@endsection
