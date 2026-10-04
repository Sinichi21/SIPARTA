@props(['profile' => null, 'snapshot' => null])

@php
    $data = $snapshot
        ? (is_array($snapshot) ? $snapshot : (array) $snapshot)
        : null;

    if (! $data && $profile) {
        $toData = function ($path) {
            if (
                ! $path
                || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)
            ) {
                return null;
            }

            $binary = \Illuminate\Support\Facades\Storage::disk('public')->get($path);
            $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($path)
                ?: 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode($binary);
        };

        $data = [
            'organization_name' => $profile->organization_name,
            'parent_organization' => $profile->parent_organization,
            'sub_parent_organization' => $profile->sub_parent_organization,
            'address' => $profile->address,
            'phone' => $profile->phone,
            'email' => $profile->email,
            'website' => $profile->website,
            'logo_data_uri' => $toData($profile->logo_path),
            'logo_secondary_data_uri' => $toData($profile->logo_secondary_path),
        ];
    }

    $images = collect([
        $data['logo_data_uri'] ?? null,
        $data['logo_secondary_data_uri'] ?? null,
    ])->filter()->values();

    $imageCount = $images->count();

    // Kop 1 logo mengikuti referensi: logo di kiri, teks rata kiri.
    // Kop 2 logo mempertahankan kelompok logo di kiri, tetapi blok teks dibuat center.
    [$imageWidth, $textWidth, $textAlign, $logoMaxWidth, $logoMaxHeight] = match (true) {
        $imageCount >= 2 => ['28%', '72%', 'center', '72px', '88px'],
        $imageCount === 1 => ['20%', '80%', 'left', '132px', '104px'],
        default => [null, '100%', 'center', null, null],
    };

    // Compatibility: old profiles with a | separator still work.
    $parentLines = collect(
        preg_split('/\r\n|\r|\n|\|/', (string) ($data['parent_organization'] ?? '')) ?: []
    )
        ->map(fn ($line) => trim($line))
        ->filter()
        ->values();

    $directorate = trim((string) ($data['sub_parent_organization'] ?? ''));
    if ($directorate !== '') {
        $parentLines = $parentLines->reject(fn ($line) => mb_strtolower($line) === mb_strtolower($directorate))->values();
    }

    $contactParts = collect([
        filled($data['address'] ?? null) ? trim((string) $data['address']) : null,
        filled($data['phone'] ?? null) ? 'Telp. '.trim((string) $data['phone']) : null,
        filled($data['email'] ?? null) ? trim((string) $data['email']) : null,
        filled($data['website'] ?? null) ? trim((string) $data['website']) : null,
    ])->filter()->values();
@endphp

@if($data)
    <header
        {{ $attributes }}
        style="
            width: 100%;
            border: 0;
            padding: 0 0 8px 0;
            margin: 0 0 24px 0;
            color: #4a4a4a;
            font-family: Arial, Helvetica, sans-serif;
        "
    >
        <table
            role="presentation"
            width="100%"
            cellspacing="0"
            cellpadding="0"
            style="
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
            "
        >
            <colgroup>
                @if($imageCount > 0)
                    <col style="width: {{ $imageWidth }};">
                @endif
                <col style="width: {{ $textWidth }};">
            </colgroup>

            <tr>
                @if($imageCount > 0)
                    <td
                        width="{{ $imageWidth }}"
                        style="
                            width: {{ $imageWidth }};
                            padding: 0 14px 0 0;
                            text-align: center;
                            vertical-align: middle;
                        "
                    >
                        @foreach($images as $img)
                            <img
                                src="{{ $img }}"
                                alt=""
                                style="
                                    display: inline-block;
                                    max-width: {{ $logoMaxWidth }};
                                    max-height: {{ $logoMaxHeight }};
                                    width: auto;
                                    height: auto;
                                    vertical-align: middle;
                                    {{ ! $loop->last ? 'margin-right: 8px;' : '' }}
                                "
                            >
                        @endforeach
                    </td>
                @endif

                <td
                    width="{{ $textWidth }}"
                    style="
                        width: {{ $textWidth }};
                        padding: 0 0 0 {{ $imageCount > 0 ? '4px' : '0' }};
                        text-align: {{ $textAlign }};
                        vertical-align: middle;
                    "
                >
                    @foreach($parentLines as $line)
                        <div
                            style="
                                margin: 0;
                                font-size: {{ $loop->first ? '13pt' : '11.4pt' }};
                                line-height: 1.08;
                                font-weight: 700;
                                letter-spacing: 0;
                                text-transform: uppercase;
                                color: #4a4a4a;
                            "
                        >
                            {{ $line }}
                        </div>
                    @endforeach

                    @if($directorate !== '')
                        <div style="margin: 1px 0 0 0; font-size: 11.4pt; line-height: 1.08; font-weight: 700; text-transform: uppercase; color: #4a4a4a;">
                            {{ $directorate }}
                        </div>
                    @endif

                    @if($data['organization_name'] ?? null)
                        <div
                            style="
                                margin: 1px 0 0 0;
                                font-size: 11.4pt;
                                line-height: 1.08;
                                font-weight: 700;
                                letter-spacing: 0;
                                text-transform: uppercase;
                                color: #4a4a4a;
                            "
                        >
                            {{ $data['organization_name'] }}
                        </div>
                    @endif

                    @if($contactParts->isNotEmpty())
                        <div
                            style="
                                margin-top: 5px;
                                font-size: 7.6pt;
                                line-height: 1.18;
                                font-weight: 400;
                                color: #555555;
                                text-transform: none;
                            "
                        >
                            {{ $contactParts->implode(', ') }}
                        </div>
                    @endif
                </td>
            </tr>
        </table>
        <div aria-hidden="true" style="border-top: 1px solid #555555; height: 0; line-height: 0; font-size: 0; margin-top: 8px;"></div>
        <div aria-hidden="true" style="border-top: 3px solid #555555; height: 0; line-height: 0; font-size: 0; margin-top: 2px;"></div>
    </header>
@endif
