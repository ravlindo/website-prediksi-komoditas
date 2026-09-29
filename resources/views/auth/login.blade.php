<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#12335f">
    <title>Login Admin — Mojokerto Harga</title>
    <link rel="icon" href="{{ asset(config('branding.favicon')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body">
    <main class="login-shell">
        <section class="login-visual" aria-label="Portal harga Kabupaten Mojokerto">
            <div class="login-visual-overlay"></div>
            <div class="login-brand"><span>MH</span><div><strong>Mojokerto Harga</strong><small>Panel Administrasi Data Bahan Pokok</small></div></div>
            <div class="login-visual-copy"><p>PORTAL INFORMASI HARGA DAERAH</p><h1>Data terjaga.<br>Keputusan lebih terpercaya.</h1><span>Akses khusus administrator pengelola data Kabupaten Mojokerto.</span></div>
        </section>
        <section class="login-panel">
            <a class="login-back" href="{{ route('dashboard') }}">← Kembali ke dashboard</a>
            <div class="login-card">
                <div class="login-mark">MH</div>
                <p class="login-eyebrow">AKSES ADMINISTRATOR</p>
                <h2>Selamat datang kembali</h2>
                <p class="login-subtitle">Masukkan akun admin untuk mengelola data harga.</p>

                @if(session('status'))<div class="login-message success">{{ session('status') }}</div>@endif
                @if($errors->any())<div class="login-message error">{{ $errors->first() }}</div>@endif

                <form method="POST" action="{{ route('login.store') }}" class="login-form">
                    @csrf
                    <label><span>Username atau email</span><div class="login-input"><svg viewBox="0 0 24 24"><path d="M4 20c0-4 3-6 8-6s8 2 8 6M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg><input name="login" value="{{ old('login') }}" autocomplete="username" required autofocus placeholder="Masukkan username atau email"></div></label>
                    <label><span>Kata sandi</span><div class="login-input"><svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><input id="loginPassword" type="password" name="password" autocomplete="current-password" required placeholder="Masukkan kata sandi"><button type="button" class="password-toggle" data-password-toggle="loginPassword" aria-label="Tampilkan kata sandi">Lihat</button></div></label>
                    <label class="remember-check"><input type="checkbox" name="remember" value="1"><span>Ingat saya di perangkat ini</span></label>
                    <button class="login-submit" type="submit">Masuk ke Panel Admin <span>→</span></button>
                </form>
                <p class="login-security"><i></i> Sesi dilindungi dan aktivitas perubahan dicatat.</p>
            </div>
        </section>
    </main>
</body>
</html>
