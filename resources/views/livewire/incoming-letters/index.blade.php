<div class="space-y-6">
    <x-app.page-heading
        title="Surat Masuk"
        description="Pencatatan, disposisi, proses, penyelesaian, dan arsip surat yang diterima."
    >
        <x-slot:actions>
            @can('incoming-letters.create')
                <a href="{{ route('incoming-letters.create') }}" wire:navigate class="spt-action spt-action-primary">
                    + Catat Surat Masuk
                </a>
            @endcan
        </x-slot:actions>
    </x-app.page-heading>

    <div class="dashboard-stats">
        <x-app.stat-card label="Total Surat" :value="number_format($total, 0, ',', '.')" description="Seluruh surat masuk" icon="document" />
        <x-app.stat-card label="Diproses" :value="number_format($processing, 0, ',', '.')" description="Sedang ditindaklanjuti" icon="clock" tone="violet" />
        <x-app.stat-card label="Selesai" :value="number_format($completed, 0, ',', '.')" description="Selesai ditindaklanjuti" icon="shield" tone="green" />
        <x-app.stat-card label="Arsip" :value="number_format($archived, 0, ',', '.')" description="Sudah diarsipkan" icon="archive" tone="amber" />
    </div>

    <section class="portal-card">
        <div class="grid gap-3 md:grid-cols-[1fr_180px_180px]">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari agenda, nomor, asal, atau perihal...">
            <select wire:model.live="status">
                <option value="">Semua status</option>
                <option value="recorded">Dicatat</option>
                <option value="disposed">Didisposisikan</option>
                <option value="processing">Diproses</option>
                <option value="completed">Selesai</option>
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
                        <th>Agenda</th>
                        <th>Nomor / Tanggal</th>
                        <th>Asal</th>
                        <th>Perihal</th>
                        <th>Diterima</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($letters as $letter)
                        <tr>
                            <td class="font-semibold">{{ $letter->agenda_number }}</td>
                            <td>
                                <strong>{{ $letter->number ?: '-' }}</strong>
                                <span class="block text-xs text-slate-500">{{ $letter->letter_date?->translatedFormat('d M Y') ?: '-' }}</span>
                            </td>
                            <td>{{ $letter->sender }}</td>
                            <td>{{ $letter->subject }}</td>
                            <td>{{ $letter->received_date?->translatedFormat('d M Y') }}</td>
                            <td><span class="status-badge status-info">{{ $letter->status->label() }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('incoming-letters.show', $letter) }}" wire:navigate class="spt-action spt-action-view">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-app.empty-state title="Belum ada surat masuk" description="Surat yang dicatat akan tampil di sini." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="portal-table-footer">{{ $letters->links() }}</div>
    </section>
</div>
