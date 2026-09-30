<div class="portal-page">
    <x-app.page-heading
        title="Register & Rekap Persuratan"
        description="Pantau surat masuk, surat keluar, dan surat terbit dalam satu register."
    >
        <x-slot:actions>
            @can('reports.export')
                <button
                    type="button"
                    wire:click="exportCsv"
                    class="spt-action spt-action-view"
                >
                    Export CSV
                </button>
            @endcan
        </x-slot:actions>
    </x-app.page-heading>

    <div class="dashboard-stats">
        <x-app.stat-card
            label="Surat Masuk"
            :value="number_format($summary['incoming'], 0, ',', '.')"
            :description="'Tahun '.($year ?: now()->year)"
            icon="document"
        />
        <x-app.stat-card
            label="Surat Keluar"
            :value="number_format($summary['outgoing'], 0, ',', '.')"
            :description="'Tahun '.($year ?: now()->year)"
            icon="document"
            tone="violet"
        />
        <x-app.stat-card
            label="Surat Terbit"
            :value="number_format($summary['issued'], 0, ',', '.')"
            :description="'Tahun '.($year ?: now()->year)"
            icon="shield"
            tone="green"
        />
        <x-app.stat-card
            label="Dicabut"
            :value="number_format($summary['revoked'], 0, ',', '.')"
            description="Dokumen terbit yang dicabut"
            icon="archive"
            tone="amber"
        />
    </div>

    <section class="portal-card">
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach([
                'incoming' => 'Surat Masuk',
                'outgoing' => 'Surat Keluar',
                'issued' => 'Surat Terbit',
            ] as $type => $label)
                <button
                    type="button"
                    wire:click="$set('documentType', '{{ $type }}')"
                    class="spt-action {{ $documentType === $type ? 'spt-action-primary' : 'spt-action-view' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="app-filter-grid">
            <label class="app-filter-field"><span>Pencarian</span><input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="Cari nomor, perihal, tujuan, asal..."
            ></label>

            <label class="app-filter-field"><span>Tahun</span><select wire:model.live="year">
                <option value="">Semua tahun</option>
                @foreach(range(now()->year, now()->year - 7) as $item)
                    <option value="{{ $item }}">{{ $item }}</option>
                @endforeach
            </select></label>

            <label class="app-filter-field"><span>Bulan</span><select wire:model.live="month">
                <option value="">Semua bulan</option>
                @foreach(range(1, 12) as $item)
                    <option value="{{ $item }}">
                        {{ \Illuminate\Support\Carbon::create(null, $item, 1)->locale('id')->translatedFormat('F') }}
                    </option>
                @endforeach
            </select></label>

            @if($documentType !== 'incoming')
                <label class="app-filter-field"><span>Jenis surat</span><select wire:model.live="letterTypeId">
                    <option value="">Semua jenis surat</option>
                    @foreach($letterTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select></label>
            @endif

            <label class="app-filter-field"><span>Status</span><select wire:model.live="status">
                <option value="">Semua status</option>

                @if($documentType === 'incoming')
                    <option value="recorded">Dicatat</option>
                    <option value="disposed">Didisposisikan</option>
                    <option value="processing">Diproses</option>
                    <option value="completed">Selesai</option>
                    <option value="archived">Diarsipkan</option>
                @elseif($documentType === 'outgoing')
                    <option value="draft">Draft</option>
                    <option value="verified">Diverifikasi</option>
                    <option value="approved">Disetujui</option>
                    <option value="published">Diterbitkan</option>
                    <option value="sent">Dikirim</option>
                    <option value="archived">Diarsipkan</option>
                @else
                    <option value="active">Aktif</option>
                    <option value="revoked">Dicabut</option>
                @endif
            </select></label>

            <button
                type="button"
                wire:click="resetFilters"
                class="spt-action spt-action-back"
            >
                Reset
            </button>
        </div>
    </section>

    <section class="portal-card portal-table-card">
        <div class="overflow-x-auto">
            <table class="portal-table">
                @if($documentType === 'incoming')
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
                        @forelse($rows as $letter)
                            <tr>
                                <td class="font-semibold">{{ $letter->agenda_number }}</td>
                                <td>
                                    <strong>{{ $letter->number ?: '-' }}</strong>
                                    <span class="block text-xs text-slate-500">
                                        {{ $letter->letter_date?->translatedFormat('d M Y') ?: '-' }}
                                    </span>
                                </td>
                                <td>{{ $letter->sender }}</td>
                                <td>{{ $letter->subject }}</td>
                                <td>{{ $letter->received_date?->translatedFormat('d M Y') }}</td>
                                <td>
                                    <span class="status-badge status-info">
                                        {{ $letter->status->label() }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <a
                                        href="{{ route('incoming-letters.show', $letter) }}"
                                        wire:navigate
                                        class="spt-action spt-action-view"
                                    >
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <x-app.empty-state
                                        title="Tidak ada surat masuk"
                                        description="Tidak ada data yang cocok dengan filter register."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                @elseif($documentType === 'outgoing')
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Jenis</th>
                            <th>Tanggal</th>
                            <th>Tujuan</th>
                            <th>Perihal</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $letter)
                            <tr>
                                <td class="font-semibold">{{ $letter->number ?: 'Draft #'.$letter->id }}</td>
                                <td>{{ $letter->letterType?->name ?: '-' }}</td>
                                <td>{{ $letter->letter_date?->translatedFormat('d M Y') ?: '-' }}</td>
                                <td>{{ $letter->recipient }}</td>
                                <td>{{ $letter->subject }}</td>
                                <td>
                                    <span class="status-badge status-info">
                                        {{ $letter->status->label() }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <a
                                        href="{{ route('outgoing-letters.show', $letter) }}"
                                        wire:navigate
                                        class="spt-action spt-action-view"
                                    >
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <x-app.empty-state
                                        title="Tidak ada surat keluar"
                                        description="Tidak ada data yang cocok dengan filter register."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                @else
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Jenis</th>
                            <th>Tanggal</th>
                            <th>Tujuan</th>
                            <th>Perihal</th>
                            <th>Status</th>
                            <th>Diterbitkan</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $letter)
                            <tr>
                                <td class="font-semibold">{{ $letter->number }}</td>
                                <td>{{ $letter->letterType?->name ?: '-' }}</td>
                                <td>{{ $letter->letter_date?->translatedFormat('d M Y') }}</td>
                                <td>{{ $letter->recipient }}</td>
                                <td>{{ $letter->subject }}</td>
                                <td>
                                    <span class="status-badge {{ $letter->status === 'revoked' ? 'status-danger' : 'status-success' }}">
                                        {{ $letter->status === 'revoked' ? 'Dicabut' : 'Aktif' }}
                                    </span>
                                </td>
                                <td>
                                    {{ $letter->issued_at?->translatedFormat('d M Y, H:i') }}
                                    <span class="block text-xs text-slate-500">
                                        {{ $letter->issuer?->name ?: '-' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <a
                                        href="{{ route('issued-letters.show', $letter) }}"
                                        wire:navigate
                                        class="spt-action spt-action-view"
                                    >
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <x-app.empty-state
                                        title="Tidak ada surat terbit"
                                        description="Tidak ada data yang cocok dengan filter register."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                @endif
            </table>
        </div>

        <div class="portal-table-footer">
            {{ $rows->links() }}
        </div>
    </section>
</div>
