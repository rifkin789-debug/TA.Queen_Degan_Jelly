@extends('layouts.app')

@section('title', 'Masuk Admin | Queen Degan Jelly')
@section('footer', '')

@section('content')
<div class="container py-5 d-flex align-items-center justify-content-center" style="min-height: 85vh;">
    <div class="qdj-card p-4 p-md-5 shadow-sm" style="max-width: 440px; width: 100%; border-top: 4px solid var(--qdj-green-dark);">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 56px; height: 56px; background-color: var(--qdj-mint); font-size: 1.75rem;">
                🛡️
            </div>
            <h1 class="h4 fw-bold text-dark mb-1">Masuk Panel Admin</h1>
            <p class="text-muted small">Ruang kerja pengelola usaha Queen Degan Jelly</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Email Admin</label>
                <input class="form-control" type="text" name="email" value="{{ old('email') }}" placeholder="admin@domain.com" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Password Admin</label>
                <input class="form-control" type="password" name="password" placeholder="••••••••••••" required>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember_admin" value="1">
                <label class="form-check-label small text-muted" for="remember_admin">Ingat saya</label>
            </div>
            <button class="btn btn-qdj w-100 py-2 fs-6 mb-0">
                Masuk ke Panel Admin
            </button>
        </form>
    </div>
</div>
@endsection
