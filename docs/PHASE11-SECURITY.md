# Phase 11 — Security & Account Recovery

## Prinsip

Administrator tidak mengatur password baru pengguna.

Tindakan **Kirim Reset Password**:
1. menggunakan Laravel password broker,
2. mengirim tautan reset ke email pengguna,
3. password lama tidak dibaca atau diubah oleh admin,
4. pengguna menentukan password baru sendiri,
5. sesi lama dicabut.

## Authenticator

SIPARTA sudah menggunakan Laravel Fortify TOTP 2FA.

Rekomendasi:
- password + Authenticator sebagai autentikasi sehari-hari,
- recovery codes disimpan pengguna,
- passkey dapat digunakan bila sesuai,
- email digunakan hanya untuk kejadian penting seperti password reset dan aktivasi/recovery.

Mail server SIMPRAM dapat digunakan sebagai SMTP SIPARTA untuk email penting.

## Tindakan admin

Permission baru:

```text
users.security.manage
```

Panel:
- kirim reset password,
- reset 2FA,
- cabut seluruh sesi,
- nonaktifkan akun,
- aktifkan akun.

Panel dilindungi:
- `auth`
- `verified`
- `password.confirm`
- permission `users.security.manage`

## Session

Direkomendasikan production:

```env
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
```

## Mail

Contoh production SMTP:

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.example.internal
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.internal
MAIL_FROM_NAME="${APP_NAME}"
```

Isi credential nyata hanya di `.env` production dan jangan commit ke Git.

## Setelah install

```powershell
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan permission:cache-reset
php artisan view:clear
```

Lalu test:

```powershell
php artisan test --compact --filter=AccountSecurityPhaseElevenTest
php artisan test --compact
npm run build
```
