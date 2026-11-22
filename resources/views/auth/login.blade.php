@extends('layouts.app')

@section('title', 'Masuk | Queen Degan Jelly')

@section('content')
<div class="container py-5" style="max-width: 460px;">
    <div class="qdj-card p-4 p-md-5">
        <div class="text-center mb-4">
            <img src="{{ asset('images/logo.png') }}" alt="Queen Degan Jelly" class="rounded-circle mb-2 shadow-sm" style="width: 64px; height: 64px; object-fit: cover; border: 2px solid var(--qdj-green-dark);">
            <h1 class="h3 fw-bold text-dark mt-2 mb-1">Masuk Pelanggan</h1>
            <p class="text-muted small">Akses akun pelanggan dan riwayat pesanan Anda.</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Email</label>
                <input class="form-control" type="text" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Password</label>
                <input class="form-control" type="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                <label class="form-check-label small text-muted" for="remember">Ingat saya</label>
            </div>
            <button class="btn btn-qdj w-100 py-2 fs-6">Masuk</button>
        </form>

        <p class="text-center text-muted small mt-4 mb-0 pt-3 border-top">
            Belum punya akun? <a class="fw-semibold text-decoration-none" style="color: var(--qdj-green-dark);" href="{{ route('register') }}">Daftar sekarang</a>
        </p>
    </div>
</div>
@endsection
