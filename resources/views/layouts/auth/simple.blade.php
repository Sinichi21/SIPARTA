<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="color-scheme: light">
    <head>
        @include('partials.head', ['forceLight' => true])
    </head>
    <body class="auth-app antialiased">
        <div class="auth-shell">
            <aside class="auth-brand-panel">
                <a href="{{ route('home') }}" class="auth-brand" wire:navigate><span class="auth-brand-symbol"><x-app.icon name="document" /></span><span><strong>Persuratan Komdigi</strong><small>Administrasi &amp; Penugasan</small></span></a>
                <div class="auth-brand-content">
                    <span class="auth-kicker">SISTEM PERSURATAN &amp; SPT</span>
                    <h2>Administrasi persuratan<br>dalam satu tempat.</h2>
                    <p>Kelola surat perintah tugas, data personil, dan riwayat penugasan dengan lebih tertata.</p>
                    <div class="auth-feature-list">
                        <div><span><x-app.icon name="document" /></span><div><strong>Persuratan terpusat</strong><p>Kelola dan telusuri surat perintah tugas.</p></div></div>
                        <div><span><x-app.icon name="users" /></span><div><strong>Personil &amp; penugasan</strong><p>Temukan data personil dan riwayat tugasnya.</p></div></div>
                        <div><span><x-app.icon name="archive" /></span><div><strong>Rekap yang terorganisir</strong><p>Tinjau penugasan dan arsip SPT dengan mudah.</p></div></div>
                    </div>
                </div>
                <p class="auth-brand-footer">Persuratan Komdigi &middot; {{ now()->year }}</p>
            </aside>
            <main class="auth-main">
                <a href="{{ route('home') }}" class="auth-mobile-brand" wire:navigate><span class="auth-brand-symbol"><x-app.icon name="document" /></span><span>Persuratan Komdigi<small>Administrasi &amp; Penugasan</small></span></a>
                <div class="auth-card">{{ $slot }}</div>
                <p class="auth-page-footer"><x-app.icon name="shield" /> Gunakan akun yang terdaftar untuk mengakses aplikasi.</p>
            </main>
        </div>
        @persist('toast')
            <flux:toast.group><flux:toast /></flux:toast.group>
        @endpersist
        @fluxScripts
    </body>
</html>
