<div class="space-y-6">
    <x-app.page-heading
        title="Surat Terbit"
        description="Register final surat yang sudah diterbitkan dari workflow Surat Keluar."
    />

    <div class="grid gap-4 sm:grid-cols-3">
        <x-app.stat-card label="Total Terbit" :value="number_format($total, 0, ',', '.')" description="Seluruh register surat terbit" icon="document" />
        <x-app.stat-card label="Tahun Ini" :value="number_format($thisYear, 0, ',', '.')" description="{{ now()->year }}" icon="calendar" tone="violet" />
        <x-app.stat-card label="Bulan Ini" :value="number_format($thisMonth, 0, ',', '.')" description="{{ now()->locale('id')->translatedFormat('F Y') }}" icon="shield" tone="green" />
    </div>

    <section class="portal-card">
        <div class="grid gap-3 md:grid-cols-[1fr_180px]">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nomor, tujuan, atau perihal...">
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
                        <th>Tanggal</th>
                        <th>Perihal</th>
                        <th>Tujuan</th>
                        <th>Diterbitkan</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($letters as $letter)
                        <tr>
                            <td class="font-semibold">{{ $letter->number }}</td>
                            <td>{{ $letter->letterType?->name ?: '-' }}</td>
                            <td>{{ $letter->letter_date?->translatedFormat('d M Y') }}</td>
                            <td>{{ $letter->subject }}</td>
                            <td>{{ $letter->recipient }}</td>
                            <td>
                                {{ $letter->issued_at?->translatedFormat('d M Y, H:i') }}
                                <span class="block text-xs text-slate-500">{{ $letter->issuer?->name ?: '-' }}</span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('issued-letters.show', $letter) }}" wire:navigate class="spt-action spt-action-view">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-app.empty-state title="Belum ada surat terbit" description="Surat akan muncul setelah workflow Surat Keluar mencapai status Diterbitkan." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="portal-table-footer">{{ $letters->links() }}</div>
    </section>
</div>
