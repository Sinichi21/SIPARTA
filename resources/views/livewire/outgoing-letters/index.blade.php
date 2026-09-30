<div class="space-y-6">
    <x-app.page-heading
        title="Surat Keluar"
        description="Penyusunan, verifikasi, persetujuan, penomoran, penerbitan, dan pengiriman surat."
    >
        <x-slot:actions>
            @can('outgoing-letters.create')
                <a href="{{ route('outgoing-letters.create') }}" wire:navigate class="spt-action spt-action-primary">
                    + Surat Keluar Baru
                </a>
            @endcan
        </x-slot:actions>
    </x-app.page-heading>

    <div class="dashboard-stats">
        <x-app.stat-card label="Draft" :value="number_format($draft, 0, ',', '.')" description="Dalam penyusunan" icon="document" />
        <x-app.stat-card label="Menunggu Proses" :value="number_format($waitingApproval, 0, ',', '.')" description="Verifikasi / persetujuan / nomor" icon="clock" tone="violet" />
        <x-app.stat-card label="Diterbitkan" :value="number_format($published, 0, ',', '.')" description="Sudah masuk register terbit" icon="shield" tone="green" />
        <x-app.stat-card label="Dikirim / Arsip" :value="number_format($sent, 0, ',', '.')" description="Sudah dikirim atau diarsipkan" icon="archive" tone="amber" />
    </div>

    <section class="portal-card">
        <div class="grid gap-3 md:grid-cols-[1fr_200px_180px]">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nomor, tujuan, atau perihal...">
            <select wire:model.live="status">
                <option value="">Semua status</option>
                <option value="draft">Draft</option>
                <option value="verified">Diverifikasi</option>
                <option value="approved">Disetujui</option>
                <option value="numbered">Bernomor</option>
                <option value="published">Diterbitkan</option>
                <option value="sent">Dikirim</option>
                <option value="archived">Diarsipkan</option>
            </select>
            <select wire:model.live="year">
                <option value="">Semua tahun</option>
                @foreach(range(now()->year, now()->year - 5) as $item)
                    <option value="{{ $item }}">{{ $item }}</option>
                @endforeach
            </select>
        </div>
    </section>

    <section class="portal-card portal-table-card">
        <div class="overflow-x-auto">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Jenis</th>
                        <th>Tujuan</th>
                        <th>Perihal</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($letters as $letter)
                        <tr>
                            <td class="font-semibold">{{ $letter->number ?: 'Draft #'.$letter->id }}</td>
                            <td>{{ $letter->letterType?->name ?: '-' }}</td>
                            <td>{{ $letter->recipient }}</td>
                            <td>{{ $letter->subject }}</td>
                            <td>{{ $letter->letter_date?->translatedFormat('d M Y') ?: '-' }}</td>
                            <td><span class="status-badge status-info">{{ $letter->status->label() }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('outgoing-letters.show', $letter) }}" wire:navigate class="spt-action spt-action-view">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-app.empty-state title="Belum ada surat keluar" description="Draft surat yang dibuat akan tampil di sini." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="portal-table-footer">{{ $letters->links() }}</div>
    </section>
</div>
