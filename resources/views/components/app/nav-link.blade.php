@props(['href', 'active' => false, 'icon' => null, 'sub' => false])
<a href="{{ $href }}" wire:navigate @click="sidebarOpen = false" @if($active) aria-current="page" @endif {{ $attributes->class(['sidebar-link', 'is-active' => $active, 'sidebar-sublink' => $sub]) }}>
    @if($icon)<x-app.icon :name="$icon" />@endif
    <span>{{ $slot }}</span>
</a>
