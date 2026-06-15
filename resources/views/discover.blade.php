@extends('layouts.app')
@section('title', 'Discover')
@section('heading', 'Discover')

@section('content')
    {{-- Hero + search --}}
    <section class="relative overflow-hidden bg-brand-light rounded-[2rem] border-b-8 border-brand p-6 sm:p-8 mb-8">
        <div class="flex items-center justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-dark leading-tight">Discover groups to join 🧭</h2>
                <p class="text-slate-600 mt-1">Find open pockets and adashi run by people you can check out first.</p>
            </div>
            <img src="{{ asset('ant-k/kdiscover.png') }}" alt="" class="hidden sm:block h-28 w-auto rounded-2xl shrink-0 drop-shadow-xl object-contain">
        </div>
        <form method="GET" class="mt-5 flex gap-2 max-w-xl">
            <input name="q" value="{{ $term }}" placeholder="Search pockets &amp; adashi…"
                   class="flex-1 rounded-xl border-2 border-white bg-white/80 focus:bg-white px-4 py-2.5 focus:border-brand focus:ring-brand">
            <button class="rounded-xl bg-brand hover:bg-brand-dark text-white font-bold px-5">Search</button>
        </form>
    </section>

    {{-- Open pockets --}}
    <div class="flex items-center gap-2 mb-4">
        <h3 class="text-lg font-extrabold">Open pockets</h3>
        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-sky-100 text-sky-700">{{ $pockets->count() }}</span>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-10">
        @forelse ($pockets as $p)
            <a href="{{ route('pockets.show', $p->id) }}" class="card-depth block bg-white rounded-[1.5rem] border-2 border-slate-100 hover:border-brand p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-sky-100 rounded-2xl h-11 w-11 shrink-0 flex items-center justify-center text-xl">👛</div>
                    <div class="min-w-0">
                        <div class="font-extrabold truncate">{{ $p->title }}</div>
                        <div class="text-xs text-slate-400 truncate">by {{ $p->organizer }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <div class="text-sm font-extrabold text-slate-800">₦{{ number_format($p->amount_per_hand) }}</div>
                        <div class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">per hand</div>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <div class="text-sm font-extrabold text-slate-800">{{ $p->month_count }} mo</div>
                        <div class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">{{ $p->year }}</div>
                    </div>
                </div>
                <div class="mt-3 text-sm font-bold text-brand-dark">View &amp; join →</div>
            </a>
        @empty
            <div class="col-span-full text-sm text-slate-500 bg-white border-2 border-dashed border-slate-300 rounded-[1.5rem] p-6 text-center">No open pockets found.</div>
        @endforelse
    </div>

    {{-- Open adashis --}}
    <div class="flex items-center gap-2 mb-4">
        <h3 class="text-lg font-extrabold">Open adashis</h3>
        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ $adashis->count() }}</span>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse ($adashis as $a)
            <a href="{{ route('adashi.show', $a->id) }}" class="card-depth block bg-white rounded-[1.5rem] border-2 border-slate-100 hover:border-brand p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-amber-100 rounded-2xl h-11 w-11 shrink-0 flex items-center justify-center text-xl">🔄</div>
                    <div class="min-w-0">
                        <div class="font-extrabold truncate">{{ $a->name }}</div>
                        <div class="text-xs text-slate-400 truncate">by {{ $a->admin }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <div class="text-sm font-extrabold text-slate-800">₦{{ number_format($a->amount_per_cycle) }}</div>
                        <div class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">per cycle</div>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <div class="text-sm font-extrabold text-slate-800">{{ $a->total_members }}</div>
                        <div class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">members</div>
                    </div>
                </div>
                <div class="mt-3 text-sm font-bold text-brand-dark">View &amp; join →</div>
            </a>
        @empty
            <div class="col-span-full text-sm text-slate-500 bg-white border-2 border-dashed border-slate-300 rounded-[1.5rem] p-6 text-center">No open adashis found.</div>
        @endforelse
    </div>
@endsection
