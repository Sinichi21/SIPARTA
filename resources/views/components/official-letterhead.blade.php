@props(['profile'=>null,'snapshot'=>null])

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

    [$imageWidth, $textWidth] = match (true) {
        $imageCount >= 2 => ['24%', '76%'],
        $imageCount === 1 => ['15%', '85%'],
        default => [null, '100%'],
    };
@endphp

@if($data)
    <header
        {{ $attributes }}
        style="
            width: 100%;
            border-bottom: 3px double #0f172a;
            padding-bottom: 10px;
            margin-bottom: 24px;
            color: #020617;
            font-family: 'Times New Roman', Times, serif;
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
                            padding: 0 10px 0 0;
                            text-align: center;
                            vertical-align: middle;
                            {{ $imageCount >= 2 ? 'border-right: 1px solid #64748b;' : '' }}
                        "
                    >
                        @foreach($images as $img)
                            <img
                                src="{{ $img }}"
                                alt=""
                                style="
                                    display: inline-block;
                                    max-width: {{ $imageCount >= 2 ? '72px' : '82px' }};
                                    max-height: 82px;
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
                        padding: 0 {{ $imageCount > 0 ? '12px' : '0' }};
                        text-align: center;
                        vertical-align: middle;
                    "
                >
                    @if($data['parent_organization'] ?? null)
                        <div
                            style="
                                font-size: 10.5pt;
                                line-height: 1.12;
                                font-weight: 700;
                                text-transform: uppercase;
                            "
                        >
                            {{ $data['parent_organization'] }}
                        </div>
                    @endif

                    <div
                        style="
                            font-size: 13.5pt;
                            line-height: 1.12;
                            font-weight: 700;
                            text-transform: uppercase;
                        "
                    >
                        {{ $data['organization_name'] ?? '' }}
                    </div>

                    @if($data['address'] ?? null)
                        <div
                            style="
                                margin-top: 4px;
                                font-size: 8.25pt;
                                line-height: 1.18;
                            "
                        >
                            {{ $data['address'] }}
                        </div>
                    @endif

                    @if(
                        ($data['phone'] ?? null)
                        || ($data['email'] ?? null)
                        || ($data['website'] ?? null)
                    )
                        <div
                            style="
                                margin-top: 3px;
                                font-size: 7.5pt;
                                line-height: 1.15;
                            "
                        >
                            @if($data['phone'] ?? null)
                                Telp. {{ $data['phone'] }}
                            @endif

                            @if($data['email'] ?? null)
                                @if($data['phone'] ?? null) · @endif
                                {{ $data['email'] }}
                            @endif

                            @if($data['website'] ?? null)
                                @if(($data['phone'] ?? null) || ($data['email'] ?? null)) · @endif
                                {{ $data['website'] }}
                            @endif
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </header>
@endif
