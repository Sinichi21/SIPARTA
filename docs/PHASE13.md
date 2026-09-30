# SIPARTA Phase 13 — Persuratan

Phase 13 menambahkan modul persuratan kantor secara **additive** tanpa membongkar modul SPT.

## Modul

### Surat Masuk
Workflow:

```text
Dicatat → Didisposisikan → Diproses → Selesai → Diarsipkan
```

Data:
- nomor agenda
- nomor/tanggal surat
- tanggal diterima
- asal surat
- perihal
- klasifikasi
- sifat
- lampiran
- tujuan
- catatan

### Surat Keluar
Workflow:

```text
Draft → Diverifikasi → Disetujui → Bernomor → Diterbitkan → Dikirim → Diarsipkan
```

Terintegrasi dengan:
- Jenis Surat
- Template Surat
- Kop & Administrasi Surat

Saat `Diterbitkan`, sistem membuat snapshot metadata pada Register Surat Terbit.

### Surat Terbit
Register final yang menyimpan:
- nomor
- jenis
- tanggal
- perihal
- tujuan
- snapshot penandatangan
- waktu terbit
- penerbit

Phase 14 akan membangun validasi dokumen/checksum/QR di atas register ini.

## Permission baru

```text
incoming-letters.view
incoming-letters.create
incoming-letters.update
incoming-letters.process
incoming-letters.archive

outgoing-letters.view
outgoing-letters.create
outgoing-letters.update
outgoing-letters.verify
outgoing-letters.approve
outgoing-letters.number
outgoing-letters.publish
outgoing-letters.send
outgoing-letters.archive

issued-letters.view
```

Role matrix ditambahkan secara additive. Role `staff` tetap hanya memiliki portal personal.

## Sidebar

Desain sidebar terbaru tidak diganti. Installer hanya menambah group:

```text
Ruang Kerja
├── Persuratan
│   ├── Surat Masuk
│   ├── Surat Keluar
│   └── Surat Terbit
├── Surat Perintah Tugas
└── Rekap & Laporan
```

## Cleanup

Installer membersihkan artefak root yang sudah mempunyai canonical copy atau hanya digunakan sebagai repair/install sementara:

- `Dashboard.php`
- `PhaseTwelveOneV2Test.php`
- root copy migration/security provider
- repair/install Phase 12.1 scripts
- `phpstan-test.zip`
- `rename_livewire_files.php`
- `spt-data-integrity-phase2.patch`

Source asli di `app/`, `database/`, dan `tests/` tidak dihapus.

## Install

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\apply-phase13-correspondence.ps1
```

Lanjut:

```powershell
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan permission:cache-reset
php artisan view:clear

php artisan test --compact --filter=PhaseThirteenCorrespondenceTest
php artisan test --compact
npm run build
```

## Catatan

Phase 13 belum menghasilkan PDF final generik untuk Surat Keluar. Metadata surat dan `content_html` sudah disiapkan agar CKEditor/PDF engine dapat dihubungkan berikutnya tanpa mengubah struktur data.
