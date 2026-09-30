# Phase 19 v2 — Workflow & UX Stabilization

Baseline: commit `14ecbee34dd8157f0707d26bc3d1dbc84859f5b4`.

Patch ini sengaja tidak mengganti desain halaman yang sudah dirapikan.

## Fokus
- hubungan satu-ke-satu SPT -> Surat Keluar;
- cegah membuka/membuat draft Surat Keluar kedua dari SPT yang sama;
- tombol Detail SPT menjadi `Lihat Surat Keluar` jika sudah ada;
- tombol `Terbitkan Surat` hanya muncul untuk SPT berstatus published;
- source SPT dimuat di Detail Surat Keluar;
- final preview tidak kembali ke mode draft pada status sent/archived;
- loading state dan confirmation pada workflow;
- success feedback untuk verifikasi, approval, send, archive, dan Surat Masuk;
- tidak menyentuh CSS global, dashboard, template, kop, numbering service, atau renderer.
