@php
    $letterData = $payload['letter'];
    $kop = $payload['letterhead'];
@endphp

@if($kop)
    <x-official-letterhead :snapshot="$kop" />
@endif

<main class="document-content">
    {!! $payload['rendered_html'] !!}
</main>

@if(($kop['signatory_name'] ?? null) || ($kop['signatory_position'] ?? null))
    <section class="signature">
        <p>
            {{ $kop['city'] ?? 'Tempat' }},
            @if($letterData['letter_date'] ?? null)
                {{ \Illuminate\Support\Carbon::parse($letterData['letter_date'])->translatedFormat('d F Y') }}
            @else
                -
            @endif
        </p>
        <p>{{ $kop['signatory_position'] ?? 'Pejabat Penandatangan' }}</p>
        <div class="signature-space"></div>

        @if($kop['signatory_name'] ?? null)
            <p class="signatory-name">{{ $kop['signatory_name'] }}</p>
        @endif

        @if($kop['signatory_nip'] ?? null)
            <p>NIP. {{ $kop['signatory_nip'] }}</p>
        @endif
    </section>
@endif
