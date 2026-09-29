@props(['status'])
<span @class(['status-badge', 'status-success' => in_array($status->value, ['published', 'approved']), 'status-danger' => $status->value === 'cancelled', 'status-neutral' => $status->value === 'archived', 'status-warning' => !in_array($status->value, ['published', 'approved', 'cancelled', 'archived'])])>{{ $status->label() }}</span>
