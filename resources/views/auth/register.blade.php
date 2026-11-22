@extends('layouts.app')

@section('title', 'Daftar Akun | Queen Degan Jelly')

@section('content')
<div class="container py-5" style="max-width: 480px;">
    <div class="qdj-card p-4 p-md-5">
        <div class="text-center mb-4">
            <img src="{{ asset('images/logo.png') }}" alt="Queen Degan Jelly" class="rounded-circle mb-2 shadow-sm" style="width: 64px; height: 64px; object-fit: cover; border: 2px solid var(--qdj-green-dark);">
            <h1 class="h3 fw-bold text-dark mt-2 mb-1">Buat Akun</h1>
            <p class="text-muted small">Daftar untuk kemudahan pemesanan Queen Degan Jelly.</p>
        </div>

        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Nama Lengkap</label>
                <input class="form-control" name="name" value="{{ old('name') }}" placeholder="Nama Anda" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Email</label>
                <input class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Password (minimal 8 karakter)</label>
                <input class="form-control" type="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold text-dark">Konfirmasi Password</label>
                <input class="form-control" type="password" name="password_confirmation" placeholder="••••••••" required>
            </div>
            <button class="btn btn-qdj w-100 py-2 fs-6">Daftar</button>
        </form>

        <p class="text-center text-muted small mt-4 mb-0 pt-3 border-top">
            Sudah punya akun? <a class="fw-semibold text-decoration-none" style="color: var(--qdj-green-dark);" href="{{ route('login') }}">Masuk di sini</a>
        </p>
    </div>
</div>
@endsection
