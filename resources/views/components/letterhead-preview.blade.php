@props(['profile'])

@if($profile)
    <x-official-letterhead
        :profile="$profile"
        {{ $attributes }}
    />
@endif
