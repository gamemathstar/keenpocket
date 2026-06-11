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
            <a href="{{ route('pockets.show', $p->id) }}" class="block bg-white rounded-xl border border-slate-200 p-4 hover:shadow-sm hover:border-brand transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-brand-light text-brand-dark">{{ $p->pocket_type }}</span>
                    @if ($p->user_id == auth()->id())<span class="text-xs text-amber-600">Owner</span>@endif
                </div>
                <div class="font-semibold truncate">{{ $p->title }}</div>
                <div class="text-sm text-slate-500 mt-1">₦{{ number_format($p->amount_per_hand) }}/hand</div>
                <div class="text-xs text-slate-400 mt-2">{{ $p->month_count }} months · {{ $p->year }}</div>
            </a>
        @empty
            <div class="col-span-full text-sm text-slate-500 bg-white border border-dashed border-slate-300 rounded-xl p-8 text-center">
                No pockets yet. <a href="{{ route('discover') }}" class="text-brand-dark font-medium">Discover one</a> or
                <a href="{{ route('pockets.create') }}" class="text-brand-dark font-medium">create your own</a>.
            </div>
        @endforelse
    </div>
@endsection
