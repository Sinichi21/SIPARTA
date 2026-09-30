@props(['title' => 'Belum ada data', 'description' => null, 'icon' => 'document'])
<div class="app-empty-state"><span><x-app.icon :name="$icon" /></span><h3>{{ $title }}</h3>@if($description)<p>{{ $description }}</p>@endif{{ $slot }}</div>
