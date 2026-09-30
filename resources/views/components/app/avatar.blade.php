<span {{ $attributes->class(['app-avatar']) }}>
    @if(auth()->user()?->profile_photo_path)
        <img src="{{ route('profile.photo', ['v' => substr(hash('sha256', auth()->user()->profile_photo_path), 0, 12)]) }}" alt="Foto profil {{ auth()->user()->name }}" />
    @else
        {{ auth()->user()?->initials() ?: 'U' }}
    @endif
</span>
