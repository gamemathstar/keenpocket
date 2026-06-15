@extends('layouts.app')
@section('title', 'Adashi')
@section('heading', 'Adashi')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
        <p class="text-slate-500">Your rotating savings groups.</p>
        <a href="{{ route('adashi.create') }}" class="bg-brand hover:bg-brand-dark text-white font-bold rounded-xl px-4 py-2.5">+ New adashi</a>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach ($adashis as $a)
            <a href="{{ route('adashi.show', $a->id) }}" class="card-depth block bg-white rounded-[1.5rem] border-2 border-slate-100 hover:border-brand p-5">
                <div class="flex items-start justify-between mb-3">
                    <div class="bg-amber-100 rounded-2xl h-11 w-11 flex items-center justify-center text-xl">🔄</div>
                    <span class="text-[11px] font-bold uppercase tracking-wide rounded-full px-2.5 py-1 bg-amber-100 text-amber-700">Cycle {{ $a->current_cycle_number }}</span>
                </div>
                <div class="font-extrabold truncate">{{ $a->name }}</div>
                <div class="flex justify-between text-sm text-slate-500 font-semibold mt-1">
                    <span>₦{{ number_format($a->amount_per_cycle) }}/cycle</span>
                    <span>{{ $a->total_members }} members</span>
                </div>
                <div class="text-xs text-slate-400 mt-2">Cycle {{ $a->current_cycle_number }} · every {{ $a->cycle_duration_days }} days</div>
            </a>
        @endforeach
        {{-- Start a new adashi --}}
        <a href="{{ route('adashi.create') }}" class="block rounded-[1.5rem] border-2 border-dashed border-slate-300 hover:border-brand text-slate-500 hover:text-brand-dark p-5 min-h-[140px] flex flex-col items-center justify-center gap-2 transition-colors">
            <span class="text-3xl">＋</span>
            <span class="font-bold">Start new adashi</span>
        </a>
    </div>
@endsection
