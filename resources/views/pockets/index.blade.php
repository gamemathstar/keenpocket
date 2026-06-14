@extends('layouts.app')
@section('title', 'My Pockets')
@section('heading', 'My Pockets')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <p class="text-slate-500 text-sm">Pockets you've joined or created.</p>
        <a href="{{ route('pockets.create') }}" class="text-sm bg-brand hover:bg-brand-dark text-white rounded-lg px-4 py-2">+ New pocket</a>
    </div>

    @php $all = $memberOf->merge($owned)->unique('id'); @endphp
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($all as $p)
            <a href="{{ route('pockets.show', $p->id) }}" class="kp-photo-card block bg-white rounded-xl border border-slate-200 overflow-hidden hover:shadow-md hover:border-brand transition">
                <div class="relative h-28 bg-gradient-to-br from-sky-100 to-emerald-100 overflow-hidden">
                    <img src="{{ asset('ant-k/kforpocket.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover object-center">
                    <span class="absolute top-2 right-2 text-[11px] font-bold uppercase tracking-wide text-white bg-black/30 rounded-full px-2 py-0.5">{{ $p->user_id == auth()->id() ? 'Owner' : ($p->status ? 'Open' : 'Closed') }}</span>
                </div>
                <div class="p-4">
                    <div class="font-semibold truncate">{{ $p->title }}</div>
                    <div class="text-sm text-slate-500 mt-1">₦{{ number_format($p->amount_per_hand) }}/hand</div>
                    <div class="text-xs text-slate-400 mt-2">{{ $p->month_count }} months · {{ $p->year }}</div>
                </div>
            </a>
        @empty
            <div class="col-span-full">
                <x-empty-state title="No pockets yet"
                    message="Discover an open pocket to join, or create your own."
                    :action="route('pockets.create')" actionLabel="Create a pocket" />
            </div>
        @endforelse
    </div>
@endsection
