@props(['profile'])

@if($profile)
    <div {{ $attributes->merge(['class' => 'border-b-[3px] border-double border-slate-900 pb-3']) }}>
        <div class="grid grid-cols-[82px_1fr_82px] items-center gap-4">
            <div class="flex justify-center">
                @if($profile->logo_path)
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($profile->logo_path) }}"
                        alt="Logo {{ $profile->organization_name }}"
                        class="max-h-20 max-w-20 object-contain"
                    >
                @endif
            </div>

            <div class="text-center text-slate-950">
                @if($profile->parent_organization)
                    <p class="text-sm font-semibold uppercase tracking-wide">
                        {{ $profile->parent_organization }}
                    </p>
                @endif

                <h2 class="text-lg font-bold uppercase leading-tight">
                    {{ $profile->organization_name }}
                </h2>

                @if($profile->address)
                    <p class="mt-1 text-[11px] leading-snug">
                        {{ $profile->address }}
                    </p>
                @endif

                @if($profile->phone || $profile->email || $profile->website)
                    <p class="mt-1 text-[10px] leading-snug">
                        @if($profile->phone) Telp. {{ $profile->phone }} @endif
                        @if($profile->email) · {{ $profile->email }} @endif
                        @if($profile->website) · {{ $profile->website }} @endif
                    </p>
                @endif
            </div>

            <div></div>
        </div>
    </div>
@endif
