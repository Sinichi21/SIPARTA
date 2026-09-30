@props(['title', 'description'])
<header class="page-heading">
    <div class="min-w-0"><h1>{{ $title }}</h1><p>{{ $description }}</p></div>
    @isset($actions)<div class="page-heading-actions">{{ $actions }}</div>@endisset
</header>
