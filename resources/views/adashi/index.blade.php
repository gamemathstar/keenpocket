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
            <a href="{{ route('adashi.show', $a->id) }}" class="kp-photo-card block bg-white rounded-xl border border-slate-200 overflow-hidden hover:shadow-md hover:border-brand transition">
                <div class="relative h-28 bg-gradient-to-br from-amber-100 to-emerald-100 overflow-hidden">
                    <img src="{{ asset('ant-k/kforadashi.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover object-center">
                    <span class="absolute top-2 right-2 text-[11px] font-bold uppercase tracking-wide text-white bg-black/30 rounded-full px-2 py-0.5">Cycle {{ $a->current_cycle_number }}</span>
                </div>
                <div class="p-4">
                    <div class="font-semibold truncate">{{ $a->name }}</div>
                    <div class="text-sm text-slate-500 mt-1">₦{{ number_format($a->amount_per_cycle) }}/cycle · {{ $a->total_members }} members</div>
                    <div class="text-xs text-slate-400 mt-2">Cycle {{ $a->current_cycle_number }} · every {{ $a->cycle_duration_days }} days</div>
                </div>
            </a>
        @empty
            <div class="col-span-full">
                <x-empty-state title="No adashi groups yet"
                    message="Start a rotating savings group and invite your circle."
                    :action="route('adashi.create')" actionLabel="Start an adashi" />
            </div>
        @endforelse
    </div>
@endsection
