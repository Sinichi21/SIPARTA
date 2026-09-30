@props(['label', 'value', 'description', 'icon' => 'document', 'tone' => ''])
<section class="dashboard-stat">
    <div class="stat-icon {{ $tone }}"><x-app.icon :name="$icon" class="size-8" /></div>
    <div class="min-w-0"><p class="text-sm font-semibold text-slate-500">{{ $label }}</p><strong>{{ $value }}</strong><p class="text-xs text-slate-500">{{ $description }}</p></div>
</section>
