<x-layouts::auth title="Lupa Kata Sandi">
    <div class="auth-form-content">
        <div class="auth-form-icon"><x-app.icon name="shield" /></div>
        <x-auth-header title="Lupa kata sandi?" description="Masukkan email akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi." />
        <x-auth-session-status class="auth-status" :status="session('status')" />
        <form method="POST" action="{{ route('password.email') }}" class="auth-form">
            @csrf
            <flux:input name="email" label="Alamat email" :value="old('email')" type="email" required autofocus autocomplete="email" placeholder="nama@instansi.go.id" />
            <flux:button variant="primary" type="submit" class="auth-submit w-full" data-test="email-password-reset-link-button">Kirim tautan reset <x-app.icon name="arrow" class="ml-2 size-4" /></flux:button>
        </form>
        <p class="auth-form-note">Periksa kotak masuk dan folder spam setelah meminta tautan reset.</p>
        <a href="{{ route('login') }}" wire:navigate class="auth-back-link"><x-app.icon name="arrow" class="size-4 rotate-180" /> Kembali ke halaman masuk</a>
    </div>
</x-layouts::auth>
