# Phase 13.1 — Surat Masuk: Placeholder Dinamis, Lampiran Opsional, Preview Cetak

Pengembangan ini mengikuti pola SIMOPRAM tanpa mengubah gaya visual Surat Masuk yang sudah dirapikan.

## Perilaku placeholder

Template tetap menggunakan:

```text
{{nama_placeholder}}
```

Sistem membagi placeholder menjadi dua kelompok.

### Data otomatis dari database/sistem

Contoh:

```text
{{nomor_agenda}}
{{nomor_surat}}
{{tanggal_surat}}
{{tanggal_diterima}}
{{asal_surat}}
{{pengirim}}
{{tujuan}}
{{perihal}}
{{klasifikasi}}
{{sifat}}
{{lampiran}}
{{catatan}}

{{instansi}}
{{instansi_induk}}
{{alamat_instansi}}
{{telepon_instansi}}
{{email_instansi}}
{{website_instansi}}
{{kota_surat}}
{{nama_penandatangan}}
{{nip_penandatangan}}
{{jabatan_penandatangan}}
```

### Data yang tidak tersedia di database

Jika template berisi, misalnya:

```text
{{nama_penerima}}
{{jabatan_penerima}}
{{nomor_referensi}}
```

maka form Surat Masuk otomatis menampilkan input untuk masing-masing placeholder tersebut.

Nilainya disimpan pada kolom JSON `placeholder_data`, sehingga penambahan placeholder baru tidak membutuhkan migration baru.

Placeholder manual tidak dipaksa wajib saat pencatatan. Jika kosong, preview tetap menampilkan token aslinya agar mudah ditemukan dan diperbaiki.

## Lampiran file

Lampiran bersifat opsional.

Format:
- PDF
- Word
- Excel
- JPG/JPEG
- PNG

Maksimal 10 MB.

Edit surat dapat:
- mempertahankan file lama,
- mengganti file,
- menghapus file lama.

## Preview cetak

Saat template dipilih, halaman detail menyediakan:
- Preview Cetak
- Cetak

Preview A4 mengikuti pola preview SPT yang sudah digunakan aplikasi.

## Tampilan

Tidak ada perubahan ke `resources/css/app.css`.

Form tetap menggunakan:
- `correspondence-page`
- `correspondence-section`
- `correspondence-fields`
- `portal-card`
- `spt-action`

Hanya ditambahkan section baru pada form yang sudah ada.
