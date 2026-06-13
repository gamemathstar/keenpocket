@props(['seed' => '', 'emoji' => '👛', 'label' => null])
@php
    $palettes = [
        'from-sky-400 to-blue-600',
        'from-emerald-400 to-teal-600',
        'from-violet-400 to-indigo-600',
        'from-amber-400 to-orange-600',
        'from-rose-400 to-pink-600',
        'from-cyan-400 to-sky-700',
    ];
    $g = $palettes[abs(crc32((string) $seed)) % count($palettes)];
@endphp
<div class="relative h-24 bg-gradient-to-br {{ $g }} flex items-end">
    <div class="absolute inset-0 opacity-25" style="background-image: radial-gradient(circle at 25% 15%, #fff 0, transparent 45%);"></div>
    <span class="absolute top-2 right-3 text-3xl" style="filter: drop-shadow(0 2px 3px rgba(0,0,0,.25));">{{ $emoji }}</span>
    @if ($label)
        <span class="relative m-3 text-[11px] font-bold uppercase tracking-wide text-white bg-black/25 rounded-full px-2 py-0.5">{{ $label }}</span>
    @endif
</div>
