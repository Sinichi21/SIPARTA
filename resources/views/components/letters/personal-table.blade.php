@props(['letters', 'offset' => 0, 'filtered' => false])
<div class="overflow-x-auto">
    <table class="portal-table">
        <thead><tr><th scope="col">No</th><th scope="col">Nomor / Tanggal SPT</th><th scope="col">Kegiatan</th><th scope="col">Periode Tugas</th><th scope="col">Lokasi</th><th scope="col">Status</th>@can('my-letters.view')<th scope="col" class="text-right">Aksi</th>@endcan</tr></thead>
        <tbody>
            @forelse($letters as $letter)
                <tr wire:key="personal-letter-{{ $letter->id }}">
                    <td class="text-slate-400">{{ $offset + $loop->iteration }}</td>
                    <td class="portal-letter-number">
                        @can('my-letters.view')<a href="{{ route('my-spt.show', $letter) }}" wire:navigate>{{ $letter->number ?: 'SPT #'.$letter->id }}</a>@else<strong>{{ $letter->number ?: 'SPT #'.$letter->id }}</strong>@endcan
                        <span>{{ $letter->letter_date?->translatedFormat('d M Y') ?: '-' }}</span>
                    </td>
                    <td class="portal-letter-subject">{{ $letter->subject ?: $letter->activityType?->name ?: '-' }}</td>
                    <td class="portal-letter-period">{{ $letter->start_date?->translatedFormat('d M Y') ?: '-' }}@if($letter->end_date && ! $letter->end_date->equalTo($letter->start_date))<span>s.d. {{ $letter->end_date->translatedFormat('d M Y') }}</span>@endif</td>
                    <td>{{ $letter->location ?: '-' }}</td>
                    <td><x-app.status-badge :status="$letter->status" /></td>
                    @can('my-letters.view')<td class="text-right"><a href="{{ route('my-spt.show', $letter) }}" wire:navigate class="spt-action spt-action-view" aria-label="Detail SPT {{ $letter->number ?: $letter->id }}"><x-app.icon name="document" /> Detail</a></td>@endcan
                </tr>
            @empty
                <tr><td colspan="{{ auth()->user()->can('my-letters.view') ? 7 : 6 }}"><x-app.empty-state :title="$filtered ? 'Tidak ada SPT yang sesuai' : 'Belum ada surat tugas'" :description="$filtered ? 'Coba kata kunci atau tahun lain untuk menemukan penugasan Anda.' : 'Surat tugas akan tampil di sini setelah penugasan Anda tersedia.'" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
