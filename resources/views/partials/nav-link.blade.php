@php
    $active = request()->routeIs($route) || request()->routeIs(str_replace('.index', '', $route).'.*');
    $indent = ($sub ?? false) ? 'pl-9' : 'px-3';
@endphp
<a href="{{ route($route) }}"
   class="flex items-center gap-3 {{ $indent }} py-2.5 rounded-xl text-xs font-extrabold uppercase tracking-wide border-2 {{ $active ? 'bg-brand-light text-brand-dark border-brand/40' : 'text-slate-500 border-transparent hover:bg-slate-100' }}">
    <span class="text-base">{{ $icon }}</span><span>{{ $label }}</span>
</a>
