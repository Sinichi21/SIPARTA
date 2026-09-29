@php($latestSpt = $selectedLatestSpt ?? $selectedHistory->first())

<x-app.detail-drawer title="Detail Personil">

    <section class="drawer-section">

        <div class="personnel-identity">

            <div
                class="personnel-avatar"
                aria-hidden="true"
            >
                {{ mb_strtoupper(
                    mb_substr(
                        $selectedPersonnel->name,
                        0,
                        1
                    )
                ) }}
            </div>

            <div class="min-w-0 flex-1">

                <h3 class="text-lg font-bold">
                    {{ $selectedPersonnel->name }}
                </h3>

                <dl class="identity-fields">

                    <dt>NIP</dt>

                    <dd>
                        {{ $selectedPersonnel->nip ?: '-' }}
                    </dd>

                    <dt>Unit/Tim</dt>

                    <dd>
                        {{ $selectedPersonnel->unit?->name ?: '-' }}
                    </dd>

                    <dt>Status</dt>

                    <dd>
                        <span
                            class="status-badge
                            {{ $selectedPersonnel->is_active
                                ? 'status-success'
                                : 'status-neutral' }}"
                        >
                            {{ $selectedPersonnel->is_active
                                ? 'Aktif'
                                : 'Tidak Aktif' }}
                        </span>
                    </dd>

                </dl>

            </div>

        </div>

        <div class="personnel-metrics">

            <div>
                <x-app.icon name="document" />

                <p>Total SPT</p>

                <strong>
                    {{ number_format($selectedTotalSpt) }}
                </strong>
            </div>

            <div>
                <x-app.icon name="calendar" />

                <p>
                    SPT Tahun {{ now()->year }}
                </p>

                <strong>
                    {{ number_format($selectedYearSpt) }}
                </strong>
            </div>

            <div>
                <x-app.icon name="clock" />

                <p>
                    Penugasan Terakhir
                </p>

                <strong>
                    {{ $latestSpt?->letter_date
                        ?->translatedFormat('d M Y') ?: '-' }}
                </strong>
            </div>

        </div>

    </section>


    <section class="drawer-section">

        <h3 class="drawer-section-title">
            SPT Terakhir
        </h3>

        @if($latestSpt)

            <div class="flex items-start gap-3">

                <span class="detail-icon">
                    <x-app.icon />
                </span>

                <h4 class="break-words text-sm font-bold">
                    {{ $latestSpt->number
                        ?: 'Draft SPT #'.$latestSpt->id }}
                </h4>

            </div>

            <dl class="detail-fields mt-5">

                <x-app.detail-row
                    label="Tanggal SPT"
                    icon="calendar"
                >
                    {{ $latestSpt->letter_date
                        ?->translatedFormat('d F Y') ?: '-' }}
                </x-app.detail-row>


                <x-app.detail-row label="Kegiatan">
                    {{ $latestSpt->subject
                        ?: $latestSpt->activityType?->name
                        ?: '-' }}
                </x-app.detail-row>


                <x-app.detail-row
                    label="Lokasi"
                    icon="pin"
                >
                    {{ $latestSpt->location ?: '-' }}
                </x-app.detail-row>


                <x-app.detail-row
                    label="Jabatan"
                    icon="users"
                >
                    {{ $selectedPersonnel->position ?: '-' }}
                </x-app.detail-row>


                <x-app.detail-row
                    label="Unit/Tim"
                    icon="building"
                >
                    {{ $selectedPersonnel->unit?->name ?: '-' }}
                </x-app.detail-row>


                <x-app.detail-row label="Jenis Record">
                    <span
                        class="status-badge
                        {{ $latestSpt->record_type?->value
                            === 'attendance_correction'
                            ? 'status-warning'
                            : 'status-success' }}"
                    >
                        {{ $latestSpt->record_type?->label()
                            ?? 'SPT Normal' }}
                    </span>
                </x-app.detail-row>

            </dl>

        @else

            <p class="detail-empty">
                Belum ada riwayat penugasan SPT.
                Pada filter yang sedang dipilih.
            </p>

        @endif

    </section>


    <section class="drawer-section">

        <div
            class="mb-4 flex flex-wrap
                   items-center justify-between gap-3"
        >

            <h3
                class="drawer-section-title
                       flex items-center gap-2"
            >
                Riwayat SPT Terbaru

                <span class="count-badge">

                    @if($showAllHistory)
                        {{ number_format($selectedTotalSpt) }} data
                    @else
                        {{ $selectedHistory->count() }}
                        dari
                        {{ number_format($selectedTotalSpt) }}
                    @endif

                </span>

            </h3>


            @if($selectedTotalSpt > 5)

                <button
                    type="button"
                    wire:click="toggleAllHistory"
                    wire:loading.attr="disabled"
                    wire:target="toggleAllHistory"
                    class="detail-button detail-button-outline"
                >

                    @if($showAllHistory)

                        <x-app.icon name="clock" />

                        Tampilkan 5 Terbaru

                    @else

                        <x-app.icon name="document" />

                        Lihat Semua Riwayat

                    @endif

                </button>

            @endif

        </div>


        <div class="detail-table-wrap">

            <table class="detail-table history-table">

                <thead>

                    <tr>
                        <th>No</th>
                        <th>Nomor SPT</th>
                        <th>Tanggal</th>
                        <th>Kegiatan</th>
                        <th>Lokasi</th>
                        <th>Jenis</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($selectedHistory as $letter)

                        <tr>

                            <td>
                                {{ $loop->iteration }}
                            </td>

                            <td>
                                {{ $letter->number
                                    ?: 'Draft #'.$letter->id }}
                            </td>

                            <td>
                                {{ $letter->letter_date
                                    ?->translatedFormat('d M Y')
                                    ?: '-' }}
                            </td>

                            <td>
                                {{ $letter->subject
                                    ?: $letter->activityType?->name
                                    ?: '-' }}
                            </td>

                            <td>
                                {{ $letter->location ?: '-' }}
                            </td>

                            <td>
                                <span
                                    class="status-badge
                                    {{ $letter->record_type?->value
                                        === 'attendance_correction'
                                        ? 'status-warning'
                                        : 'status-success' }}"
                                >
                                    {{ $letter->record_type?->label()
                                        ?? 'SPT Normal' }}
                                </span>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="6"
                                class="detail-empty"
                            >
                                Belum ada riwayat SPT
                                pada filter aktif.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </section>


    <x-slot:footer>

        @can('personnels.update')

            <a
                href="{{ route(
                    'personnels.edit',
                    $selectedPersonnel
                ) }}"
                wire:navigate
                class="detail-button
                       detail-button-outline"
            >
                <x-app.icon name="users" />

                Data Personil
            </a>

        @endcan


        <button
            type="button"
            wire:click="exportHistory"
            wire:loading.attr="disabled"
            wire:target="exportHistory"
            class="detail-button
                   detail-button-primary"
            title="Unduh seluruh riwayat sesuai filter dalam format CSV"
        >
            <x-app.icon name="download" />

            <span
                wire:loading.remove
                wire:target="exportHistory"
            >
                Export Riwayat
            </span>

            <span
                wire:loading
                wire:target="exportHistory"
            >
                Menyiapkan...
            </span>
        </button>

    </x-slot:footer>

</x-app.detail-drawer>