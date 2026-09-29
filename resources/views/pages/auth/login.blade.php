<x-layouts::auth title="Masuk">
    <div class="auth-form-content">
        <div class="auth-form-icon"><x-app.icon name="document" /></div>
        <x-auth-header title="Selamat datang kembali" description="Masuk untuk mengelola persuratan dan penugasan Anda." />
        <x-auth-session-status class="auth-status" :status="session('status')" />
        <x-passkey-verify label="Masuk dengan passkey" loading-label="Memverifikasi..." separator="atau masuk dengan email" />
        <form method="POST" action="{{ route('login.store') }}" class="auth-form">
            @csrf
            <flux:input name="email" label="Alamat email" :value="old('email')" type="email" required autofocus autocomplete="email" placeholder="nama@instansi.go.id" />
            <flux:input name="password" label="Kata sandi" type="password" required autocomplete="current-password" placeholder="Masukkan kata sandi" viewable />
            <div class="auth-form-options">
                <flux:checkbox name="remember" label="Ingat saya" :checked="old('remember')" />
                @if(Route::has('password.request'))<flux:link :href="route('password.request')" wire:navigate>Lupa kata sandi?</flux:link>@endif
            </div>
            <flux:button variant="primary" type="submit" class="auth-submit w-full" data-test="login-button">Masuk <x-app.icon name="arrow" class="ml-2 size-4" /></flux:button>
        </form>
        <p class="auth-form-note">Belum memiliki akun? Hubungi administrator untuk mendapatkan akses.</p>
    </div>
</x-layouts::auth>
