<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Surat — SIPARTA</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f4f7fb;
            color: #172033;
            font-family: Arial, sans-serif;
        }
        .shell {
            width: min(760px, calc(100% - 32px));
            margin: 48px auto;
        }
        .brand {
            margin-bottom: 18px;
            color: #174a7e;
            font-weight: 800;
            letter-spacing: .02em;
        }
        .card {
            overflow: hidden;
            border: 1px solid #d9e2ec;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 18px 50px rgba(34, 62, 94, .08);
        }
        .status {
            padding: 20px 24px;
            background: #ecfdf3;
            border-bottom: 1px solid #ccebd8;
        }
        .status.revoked {
            background: #fff1f2;
            border-bottom-color: #fecdd3;
        }
        .status.revoked strong { color: #991b1b; }
        .status.revoked span { color: #9f1239; }
        .status strong {
            display: block;
            color: #17643a;
            font-size: 18px;
        }
        .status span {
            display: block;
            margin-top: 5px;
            color: #4d6c5c;
            font-size: 13px;
        }
        .content { padding: 24px; }
        dl {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 24px;
            margin: 0;
        }
        dt {
            color: #6b778c;
            font-size: 12px;
        }
        dd {
            margin: 5px 0 0;
            font-size: 14px;
            font-weight: 650;
            line-height: 1.5;
        }
        .wide { grid-column: 1 / -1; }
        .hash {
            word-break: break-all;
            border-radius: 10px;
            background: #101827;
            padding: 12px;
            color: #e7edf5;
            font-family: monospace;
            font-size: 11px;
            font-weight: 400;
        }
        .meta {
            margin-top: 24px;
            border-top: 1px solid #edf1f5;
            padding-top: 18px;
            color: #6c7788;
            font-size: 12px;
            line-height: 1.7;
        }
        @media (max-width: 620px) {
            .shell { margin: 24px auto; }
            dl { grid-template-columns: 1fr; }
            .wide { grid-column: auto; }
        }
    </style>
</head>
<body>
    <main class="shell">
        <div class="brand">SIPARTA · Verifikasi Dokumen</div>

        <section class="card">
            <header class="status {{ $issued->isRevoked() ? 'revoked' : '' }}">
                @if($issued->isRevoked())
                    <strong>Dokumen resmi telah dicabut</strong>
                    <span>
                        Arsip tetap tersimpan untuk riwayat, namun dokumen ini tidak lagi berstatus aktif.
                    </span>
                @else
                    <strong>Dokumen terdaftar sebagai surat resmi</strong>
                    <span>
                        Data di bawah berasal dari register Surat Terbit SIPARTA.
                    </span>
                @endif
            </header>

            <div class="content">
                <dl>
                    <div>
                        <dt>Nomor Surat</dt>
                        <dd>{{ $issued->number }}</dd>
                    </div>

                    <div>
                        <dt>Tanggal Surat</dt>
                        <dd>{{ $issued->letter_date?->translatedFormat('d F Y') }}</dd>
                    </div>

                    <div>
                        <dt>Jenis Surat</dt>
                        <dd>{{ $issued->letterType?->name ?: '-' }}</dd>
                    </div>

                    <div>
                        <dt>Status</dt>
                        <dd>{{ $issued->isRevoked() ? 'Dicabut' : ucfirst($issued->status) }}</dd>
                    </div>

                    <div class="wide">
                        <dt>Perihal</dt>
                        <dd>{{ $issued->subject }}</dd>
                    </div>

                    <div>
                        <dt>Tujuan</dt>
                        <dd>{{ $issued->recipient }}</dd>
                    </div>

                    <div>
                        <dt>Penandatangan</dt>
                        <dd>{{ $issued->signatory_name ?: '-' }}</dd>
                    </div>

                    <div>
                        <dt>Jabatan</dt>
                        <dd>{{ $issued->signatory_position ?: '-' }}</dd>
                    </div>

                    <div>
                        <dt>Waktu Terbit</dt>
                        <dd>{{ $issued->issued_at?->translatedFormat('d F Y, H:i') }}</dd>
                    </div>

                    @if($issued->isRevoked())
                        <div>
                            <dt>Tanggal Pencabutan</dt>
                            <dd>{{ $issued->revoked_at?->translatedFormat('d F Y, H:i') }}</dd>
                        </div>

                        <div class="wide">
                            <dt>Alasan Pencabutan</dt>
                            <dd>{{ $issued->revocation_reason }}</dd>
                        </div>
                    @endif

                    <div class="wide">
                        <dt>Checksum Snapshot SHA-256</dt>
                        <dd class="hash">{{ $issued->checksum_sha256 ?: '-' }}</dd>
                    </div>
                </dl>

                <div class="meta">
                    Verifikasi ke-{{ number_format($issued->verification_count, 0, ',', '.') }}.
                    @if($issued->last_verified_at)
                        Pemeriksaan terakhir tercatat {{ $issued->last_verified_at->translatedFormat('d F Y, H:i') }}.
                    @endif
                    <br>
                    Halaman ini hanya menampilkan metadata verifikasi; isi dokumen lengkap tidak dipublikasikan.
                </div>
            </div>
        </section>
    </main>
</body>
</html>
