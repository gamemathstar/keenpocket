@props(['icon' => '✨', 'value' => '', 'label' => '', 'tone' => 'blue'])
@php
    $tones = [
        'blue' => 'bg-brand-light text-brand-dark',
        'amber' => 'bg-amber-100 text-amber-700',
        'green' => 'bg-emerald-100 text-emerald-700',
        'purple' => 'bg-violet-100 text-violet-700',
    ];
    $tone = $tones[$tone] ?? $tones['blue'];
@endphp
<div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-4 flex items-center gap-3">
    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl text-2xl shrink-0 {{ $tone }}">{{ $icon }}</span>
    <div class="min-w-0">
        <div class="text-2xl font-extrabold leading-none truncate">{{ $value }}</div>
        <div class="text-xs text-slate-500 mt-1 uppercase tracking-wide font-bold">{{ $label }}</div>
    </div>
</div>
