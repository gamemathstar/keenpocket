@extends('layouts.app')
@section('title', 'My Pockets')
@section('heading', 'My Pockets')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
        <p class="text-slate-500">Manage your community contributions and goals.</p>
        <a href="{{ route('pockets.create') }}" class="bg-brand hover:bg-brand-dark text-white font-bold rounded-xl px-4 py-2.5">+ Create new pocket</a>
    </div>

    @php $ownedIds = $owned->pluck('id'); $joined = $memberOf->reject(fn ($p) => $ownedIds->contains($p->id)); @endphp

    {{-- Pockets I organise --}}
    <section class="mb-10">
        <h3 class="flex items-center gap-2 text-lg font-extrabold mb-4"><span>🛠️</span> Pockets I organise</h3>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($owned as $p)
                <a href="{{ route('pockets.show', $p->id) }}" class="card-depth block bg-white rounded-[1.5rem] border-2 border-slate-100 hover:border-brand p-5">
                    <div class="flex items-start justify-between mb-3">
                        <div class="bg-sky-100 rounded-2xl h-11 w-11 flex items-center justify-center text-xl">👛</div>
                        <span class="text-[11px] font-bold uppercase tracking-wide rounded-full px-2.5 py-1 {{ $p->status ? 'bg-brand-light text-brand-dark' : 'bg-slate-100 text-slate-500' }}">{{ $p->status ? 'Active' : 'Closed' }}</span>
                    </div>
                    <div class="font-extrabold truncate">{{ $p->title }}</div>
                    <div class="text-sm text-slate-500 font-semibold mt-1">₦{{ number_format($p->amount_per_hand) }}/hand · {{ $p->month_count }} months · {{ $p->year }}</div>
                </a>
            @endforeach
            {{-- Start a new pocket --}}
            <a href="{{ route('pockets.create') }}" class="block rounded-[1.5rem] border-2 border-dashed border-slate-300 hover:border-brand text-slate-500 hover:text-brand-dark p-5 min-h-[140px] flex flex-col items-center justify-center gap-2 transition-colors">
                <span class="text-3xl">＋</span>
                <span class="font-bold">Start new pocket</span>
            </a>
        </div>
    </section>

    {{-- Pockets I'm in --}}
    @if ($joined->count())
        <section>
            <h3 class="flex items-center gap-2 text-lg font-extrabold mb-4"><span>🤝</span> Pockets I'm in</h3>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($joined as $p)
                    <a href="{{ route('pockets.show', $p->id) }}" class="card-depth block bg-white rounded-[1.5rem] border-2 border-slate-100 hover:border-brand p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="bg-amber-100 rounded-2xl h-11 w-11 flex items-center justify-center text-xl">🤝</div>
                            <span class="text-[11px] font-bold uppercase tracking-wide rounded-full px-2.5 py-1 {{ $p->status ? 'bg-brand-light text-brand-dark' : 'bg-slate-100 text-slate-500' }}">{{ $p->status ? 'Active' : 'Closed' }}</span>
                        </div>
                        <div class="font-extrabold truncate">{{ $p->title }}</div>
                        <div class="text-sm text-slate-500 font-semibold mt-1">₦{{ number_format($p->amount_per_hand) }}/hand · {{ $p->month_count }} months · {{ $p->year }}</div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
