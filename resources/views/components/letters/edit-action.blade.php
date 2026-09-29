@props(['letter', 'showDisabled' => false])
@can('letters.update')
    @if($letter->canBeEdited())
        <a href="{{ route('letters.edit', $letter) }}" wire:navigate {{ $attributes->class(['spt-action spt-action-edit']) }}><x-app.icon name="edit" /> Edit SPT</a>
    @elseif($showDisabled)
        <button type="button" disabled title="SPT dari sistem hanya dapat diedit saat berstatus draft." {{ $attributes->class(['spt-action spt-action-disabled']) }}><x-app.icon name="edit" /> Edit terkunci</button>
    @endif
@endcan
