@extends('layouts.app')
@section('title', 'Discover')
@section('heading', 'Discover')

@section('content')
    <div class="rounded-2xl overflow-hidden border border-slate-200 mb-6">
        <img src="{{ asset('ant-k/kdiscover.png') }}" alt="Discover groups to join" class="w-full h-40 sm:h-48 object-cover object-center">
    </div>

    <form method="GET" class="mb-6 flex gap-2 max-w-md">
        <input name="q" value="{{ $term }}" placeholder="Search pockets & adashi…" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
        <button class="rounded-lg bg-brand hover:bg-brand-dark text-white px-4">Search</button>
    </form>

    <h3 class="text-lg font-semibold mb-3">Open pockets</h3>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        @forelse ($pockets as $p)
            <a href="{{ route('pockets.show', $p->id) }}" class="block bg-white rounded-xl border border-slate-200 p-4 hover:shadow-sm hover:border-brand transition">
                <div class="font-semibold truncate">{{ $p->title }}</div>
                <div class="text-sm text-slate-500 mt-1">₦{{ number_format($p->amount_per_hand) }}/hand</div>
                <div class="text-xs text-slate-400 mt-2">by {{ $p->organizer }}</div>
            </a>
        @empty
            <div class="col-span-full text-sm text-slate-500 bg-white border border-dashed border-slate-300 rounded-xl p-6 text-center">No open pockets found.</div>
        @endforelse
    </div>

    <h3 class="text-lg font-semibold mb-3">Open adashis</h3>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($adashis as $a)
            <a href="{{ route('adashi.show', $a->id) }}" class="block bg-white rounded-xl border border-slate-200 p-4 hover:shadow-sm hover:border-brand transition">
                <div class="font-semibold truncate">{{ $a->name }}</div>
                <div class="text-sm text-slate-500 mt-1">₦{{ number_format($a->amount_per_cycle) }}/cycle · {{ $a->total_members }} members</div>
                <div class="text-xs text-slate-400 mt-2">by {{ $a->admin }}</div>
            </a>
        @empty
            <div class="col-span-full text-sm text-slate-500 bg-white border border-dashed border-slate-300 rounded-xl p-6 text-center">No open adashis found.</div>
        @endforelse
    </div>
@endsection
