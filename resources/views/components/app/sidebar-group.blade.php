@props(['id', 'label', 'icon', 'active' => false])
<section class="sidebar-accordion" :class="{ 'is-open': openGroup === '{{ $id }}' }">
    <button
        id="sidebar-trigger-{{ $id }}"
        type="button"
        @click="openGroup = openGroup === '{{ $id }}' ? null : '{{ $id }}'"
        :aria-expanded="openGroup === '{{ $id }}'"
        aria-controls="sidebar-panel-{{ $id }}"
        @class(['sidebar-link sidebar-group-toggle', 'is-parent-active' => $active])
    >
        <x-app.icon :name="$icon" />
        <span>{{ $label }}</span>
        @if($active)<span class="sidebar-active-dot" aria-hidden="true"></span>@endif
        <x-app.icon name="chevron" class="sidebar-chevron" />
    </button>
    <div id="sidebar-panel-{{ $id }}" x-cloak :hidden="openGroup !== '{{ $id }}'" @if(!$active) hidden @endif role="region" aria-labelledby="sidebar-trigger-{{ $id }}">
        <div class="sidebar-submenu">{{ $slot }}</div>
    </div>
</section>
