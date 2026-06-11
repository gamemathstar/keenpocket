@props(['percent' => 0, 'label' => '', 'sublabel' => '', 'size' => 132])
@php
    $p = max(0, min(100, (int) $percent));
    $r = 52;
    $circ = 2 * M_PI * $r;
    $offset = $circ * (1 - $p / 100);
@endphp
<div class="relative inline-flex items-center justify-center" style="width: {{ $size }}px; height: {{ $size }}px">
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 120 120">
        <g transform="rotate(-90 60 60)">
            <circle cx="60" cy="60" r="{{ $r }}" fill="none" stroke="#e5e7eb" stroke-width="12"/>
            <circle cx="60" cy="60" r="{{ $r }}" fill="none" stroke="#1cb0f6" stroke-width="12" stroke-linecap="round"
                    stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $offset }}"/>
        </g>
    </svg>
    <div class="absolute inset-0 flex flex-col items-center justify-center">
        <span class="text-2xl font-extrabold text-brand-dark leading-none">{{ $label }}</span>
        @if ($sublabel)<span class="text-[11px] text-slate-500 mt-1 uppercase font-bold tracking-wide">{{ $sublabel }}</span>@endif
    </div>
</div>
