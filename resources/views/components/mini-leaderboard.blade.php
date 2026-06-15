@props(['rows', 'title' => 'Top contributors'])
<div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
    <h3 class="font-semibold mb-3">🏆 {{ $title }}</h3>
    @if (count($rows))
        <ul class="divide-y divide-slate-100">
            @foreach ($rows as $i => $r)
                <li class="py-2 flex items-center justify-between text-sm">
                    <span class="flex items-center gap-3">
                        <span class="w-6 text-center font-extrabold">{{ ['🥇', '🥈', '🥉'][$i] ?? ($i + 1) }}</span>
                        <span class="font-bold">{{ $r->name }}</span>
                    </span>
                    <span class="font-extrabold text-brand-dark">{{ number_format($r->total) }} <span class="text-xs text-slate-400 font-bold">pts</span></span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm text-slate-500">No contributions yet — be the first!</p>
    @endif
</div>
