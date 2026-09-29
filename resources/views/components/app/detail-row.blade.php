@props(['label', 'icon' => 'document'])
<div class="detail-row">
    <dt><x-app.icon :name="$icon" /><span>{{ $label }}</span></dt>
    <dd>{{ $slot }}</dd>
</div>
