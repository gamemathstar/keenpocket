@extends('layouts.app')
@section('title', 'Adashi')
@section('heading', 'Adashi')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <p class="text-slate-500 text-sm">Your rotating savings groups.</p>
        <a href="{{ route('adashi.create') }}" class="text-sm bg-brand hover:bg-brand-dark text-white rounded-lg px-4 py-2">+ New adashi</a>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($adashis as $a)
            <a href="{{ route('adashi.show', $a->id) }}" class="block bg-white rounded-xl border border-slate-200 p-4 hover:shadow-sm hover:border-brand transition">
                <div class="font-semibold truncate">{{ $a->name }}</div>
                <div class="text-sm text-slate-500 mt-1">₦{{ number_format($a->amount_per_cycle) }}/cycle · {{ $a->total_members }} members</div>
                <div class="text-xs text-slate-400 mt-2">Cycle {{ $a->current_cycle_number }} · every {{ $a->cycle_duration_days }} days</div>
            </a>
        @empty
            <div class="col-span-full text-sm text-slate-500 bg-white border border-dashed border-slate-300 rounded-xl p-8 text-center">
                No adashi groups yet. <a href="{{ route('adashi.create') }}" class="text-brand-dark font-medium">Start one →</a>
            </div>
        @endforelse
    </div>
@endsection
