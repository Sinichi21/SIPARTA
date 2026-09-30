# Phase 15.2 v1.1 — DOMPDF Letterhead Width Fix

Perbaikan ini khusus untuk kasus:
- preview Setting Kop benar;
- preview Template benar;
- preview A4 browser benar;
- tetapi PDF DOMPDF membuat kolom tengah kop terlalu sempit sehingga nama instansi turun banyak baris.

Penyebab:
DOMPDF tidak selalu menghitung lebar table-cell seperti browser, terutama bila kolom samping hanya memakai ukuran px dan kolom tengah mengandalkan sisa lebar otomatis.

Perbaikan:
- colgroup eksplisit 15% / 70% / 15%;
- atribut HTML `width` ikut dipasang karena DOMPDF lebih konsisten membacanya;
- table tetap `width=100%` dan `table-layout: fixed`;
- ukuran logo dibuat eksplisit;
- garis double tetap menggunakan komponen yang sama.

Tidak mengubah business logic atau data.
