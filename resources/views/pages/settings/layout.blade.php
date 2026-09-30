<div class="settings-layout">
    <nav class="settings-nav portal-card" aria-label="Pengaturan akun">
        @foreach([['profile.edit', 'users', 'Profil Saya', 'Identitas dan kepegawaian'], ['security.edit', 'shield', 'Keamanan', 'Password dan autentikasi'], ['appearance.edit', 'sun', 'Tampilan', 'Preferensi tema aplikasi']] as [$route, $icon, $label, $description])
        <a href="{{ route($route) }}" wire:navigate @class(['settings-nav-link', 'is-active' => request()->routeIs($route)]) @if(request()->routeIs($route)) aria-current="page" @endif><x-app.icon :name="$icon" /><span><strong>{{ $label }}</strong><small>{{ $description }}</small></span><x-app.icon name="arrow" /></a>
        @endforeach
    </nav>
    <div class="settings-content portal-card">
        <header class="settings-content-heading"><h2>{{ $heading ?? '' }}</h2><p>{{ $subheading ?? '' }}</p></header>
        {{ $slot }}
    </div>
</div>
