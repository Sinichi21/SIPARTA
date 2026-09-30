@php
    $letterData = $payload['letter'];
    $kop = $payload['letterhead'];
@endphp

@if($kop)
    <header class="letterhead">
        <div class="letterhead-grid">
            <div class="letterhead-logo">
                @if($kop['logo_data_uri'] ?? null)
                    <img src="{{ $kop['logo_data_uri'] }}" alt="">
                @endif
            </div>

            <div class="letterhead-center">
                @if($kop['parent_organization'] ?? null)
                    <div class="parent-org">{{ $kop['parent_organization'] }}</div>
                @endif

                <div class="organization">{{ $kop['organization_name'] ?? '' }}</div>

                @if($kop['address'] ?? null)
                    <div class="contact">{{ $kop['address'] }}</div>
                @endif

                @if(($kop['phone'] ?? null) || ($kop['email'] ?? null) || ($kop['website'] ?? null))
                    <div class="contact">
                        @if($kop['phone'] ?? null) Telp. {{ $kop['phone'] }} @endif
                        @if($kop['email'] ?? null) · {{ $kop['email'] }} @endif
                        @if($kop['website'] ?? null) · {{ $kop['website'] }} @endif
                    </div>
                @endif
            </div>

            <div></div>
        </div>
    </header>
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
