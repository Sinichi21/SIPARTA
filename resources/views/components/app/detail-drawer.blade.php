@props(['title'])
<div class="recap-detail" x-data="{ wide: window.innerWidth >= 1536 }" @resize.window.debounce.150ms="wide = window.innerWidth >= 1536" @keydown.escape.window="$wire.closeDetail()">
    <div class="detail-backdrop" x-show="!wide" wire:click="closeDetail" aria-hidden="true"></div>
    <aside class="recap-drawer" role="dialog" :aria-modal="!wide" aria-label="{{ $title }}" x-trap.inert.noscroll="!wide" x-init="$nextTick(() => $refs.close.focus())">
        <header class="drawer-header">
            <h2>{{ $title }}</h2>
            <button type="button" x-ref="close" wire:click="closeDetail" class="drawer-close" aria-label="Tutup {{ $title }}"><x-app.icon name="close" /></button>
        </header>
        <div class="drawer-body">{{ $slot }}</div>
        @isset($footer)<footer class="drawer-footer">{{ $footer }}</footer>@endisset
    </aside>
</div>
