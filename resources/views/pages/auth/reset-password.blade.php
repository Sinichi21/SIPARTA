<x-layouts::auth title="Reset Kata Sandi">
    <div class="auth-form-content">
        <div class="auth-form-icon"><x-app.icon name="shield" /></div>
        <x-auth-header title="Atur kata sandi baru" description="Buat kata sandi baru untuk kembali mengakses akun Anda." />
        <x-auth-session-status class="auth-status" :status="session('status')" />
        <form method="POST" action="{{ route('password.update') }}" class="auth-form">
            @csrf
            <input type="hidden" name="token" value="{{ request()->route('token') }}">
            <flux:input name="email" :value="old('email', request('email'))" label="Alamat email" type="email" required autocomplete="email" placeholder="nama@instansi.go.id" />
            <flux:input name="password" label="Kata sandi baru" type="password" required autofocus autocomplete="new-password" placeholder="Masukkan kata sandi baru" passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" viewable />
            <flux:input name="password_confirmation" label="Konfirmasi kata sandi" type="password" required autocomplete="new-password" placeholder="Ulangi kata sandi baru" passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" viewable />
            <flux:button type="submit" variant="primary" class="auth-submit w-full" data-test="reset-password-button">Simpan kata sandi baru</flux:button>
        </form>
        <a href="{{ route('login') }}" wire:navigate class="auth-back-link"><x-app.icon name="arrow" class="size-4 rotate-180" /> Kembali ke halaman masuk</a>
    </div>
</x-layouts::auth>
