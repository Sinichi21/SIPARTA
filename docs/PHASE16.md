# Phase 16 — SPT → Surat Keluar, Tabel Personil, dan Dua Gambar Kop

## Alur baru
1. Buka Detail SPT.
2. Klik **Terbitkan Surat**.
3. Sistem membuka form Surat Keluar dengan `source_spt`.
4. Jenis surat mengikuti SPT.
5. Template default SPT dipilih otomatis bila tersedia, tetapi tetap dapat diganti di halaman Surat Keluar.
6. Kegiatan, lokasi, periode, dasar, dan personil diambil dari SPT.
7. Nomor Surat Keluar TIDAK menyalin nomor SPT sumber. Nomor final tetap mengikuti sequence Surat Keluar saat Publish.
8. `{{personil}}` dan `{{personil_tabel}}` dirender sebagai tabel:
   `No | Nama | NIP | Jabatan`.

## Surat penugasan non-SPT
Jika `LetterType.requires_personnel = true`, halaman Surat Keluar menampilkan pemilih personil dari master data.

## Kop dua gambar
- 0 gambar: teks kop memakai seluruh lebar.
- 1 gambar: gambar di kiri; tidak ada kolom kosong untuk gambar kedua, sehingga area teks memakai sisa header sepenuhnya.
- 2 gambar: kedua gambar dikelompokkan di kiri dan diberi pembatas vertikal di sisi kanan kelompok gambar.
- Desain halaman Setting tidak dirombak; hanya field upload kedua ditambahkan pada grid yang sama.
