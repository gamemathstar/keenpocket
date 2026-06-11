@props(['percent' => 0, 'label' => '', 'current' => null, 'target' => null])
@php $p = max(0, min(100, (int) $percent)); @endphp
<div>
    @if ($label || $current !== null)
        <div class="flex justify-between items-end text-xs mb-1">
            <span class="text-slate-500 uppercase tracking-wide font-bold">{{ $label }}</span>
            @if ($current !== null)
                <span class="text-brand-dark font-extrabold">₦{{ number_format($current) }}@if ($target !== null)<span class="text-slate-400 font-bold"> / ₦{{ number_format($target) }}</span>@endif</span>
            @endif
        </div>
    @endif
    <div class="h-3.5 rounded-full bg-slate-100 overflow-hidden">
        <div class="h-full rounded-full bg-brand transition-all duration-500" style="width: {{ $p }}%"></div>
    </div>
</div>
