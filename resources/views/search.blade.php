@extends('layouts.app')
@section('title', 'Search')
@section('heading', 'Search')

@section('content')
    <form method="GET" action="{{ route('search') }}" class="mb-6 flex gap-2 max-w-md">
        <input name="q" value="{{ $q }}" autofocus placeholder="Search pockets & adashi…" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
        <button class="rounded-lg bg-brand hover:bg-brand-dark text-white px-4">Search</button>
    </form>

    @if ($q === '')
        <p class="text-sm text-slate-500">Type a name to search.</p>
    @else
        <p class="text-sm text-slate-500 mb-4">Results for "<span class="font-medium text-slate-700">{{ $q }}</span>"</p>

        <h3 class="font-semibold mb-2">Pockets ({{ $pockets->count() }})</h3>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-8">
            @forelse ($pockets as $p)
                <a href="{{ route('pockets.show', $p->id) }}" class="block bg-white rounded-xl border border-slate-200 p-4 hover:border-brand transition">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-brand-light text-brand-dark">{{ $p->pocket_type }}</span>
                    <div class="font-medium truncate mt-2">{{ $p->title }}</div>
                    <div class="text-xs text-slate-500 mt-1">₦{{ number_format($p->amount_per_hand) }}/hand</div>
                </a>
            @empty
                <p class="text-sm text-slate-500">No matching pockets.</p>
            @endforelse
        </div>

        <h3 class="font-semibold mb-2">Adashi ({{ $adashis->count() }})</h3>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse ($adashis as $a)
                <a href="{{ route('adashi.show', $a->id) }}" class="block bg-white rounded-xl border border-slate-200 p-4 hover:border-brand transition">
                    <div class="font-medium truncate">{{ $a->name }}</div>
                    <div class="text-xs text-slate-500 mt-1">₦{{ number_format($a->amount_per_cycle) }}/cycle</div>
                </a>
            @empty
                <p class="text-sm text-slate-500">No matching adashi.</p>
            @endforelse
        </div>
    @endif
@endsection
