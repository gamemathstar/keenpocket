@extends('layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Hello, {{ explode(' ', $user->name)[0] }} 👋</h2>
        <p class="text-slate-500 text-sm">Here's your savings at a glance.</p>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @php
            $cards = [
                ['Active pockets', $pockets->count(), '👛'],
                ['Adashi groups', $adashis->count(), '🔄'],
                ['Reputation', ($rep['band'] ?? 'New'), '⭐'],
                ['Wallet', is_null($walletBalance) ? '—' : '₦'.number_format($walletBalance), '💳'],
            ];
        @endphp
        @foreach ($cards as [$label, $value, $icon])
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="text-2xl mb-1">{{ $icon }}</div>
                <div class="text-2xl font-semibold">{{ $value }}</div>
                <div class="text-xs text-slate-500">{{ $label }}</div>
            </div>
        @endforeach
    </div>

    @if ($profile)
        <div class="bg-gradient-to-r from-brand to-emerald-600 text-white rounded-xl p-5 mb-8 flex items-center justify-between">
            <div>
                <div class="text-sm opacity-90">Savings streak</div>
                <div class="text-3xl font-bold">{{ $profile['streak'] }} 🔥</div>
            </div>
            <div class="flex flex-wrap gap-2 justify-end max-w-md">
                @foreach (collect($profile['badges'])->where('earned', true)->take(5) as $b)
                    <span class="bg-white/20 rounded-full px-3 py-1 text-xs font-medium">{{ $b['label'] }}</span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Pockets --}}
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold">My Pockets</h3>
        <a href="{{ route('pockets.create') }}" class="text-sm bg-brand hover:bg-brand-dark text-white rounded-lg px-3 py-1.5">+ New pocket</a>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        @forelse ($pockets as $p)
            <a href="{{ route('pockets.show', $p->id) }}" class="block bg-white rounded-xl border border-slate-200 p-4 hover:shadow-sm hover:border-brand transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-brand-light text-brand-dark">{{ $p->pocket_type }}</span>
                    <span class="text-xs {{ $p->status ? 'text-emerald-600' : 'text-slate-400' }}">{{ $p->status ? 'Open' : 'Closed' }}</span>
                </div>
                <div class="font-semibold truncate">{{ $p->title }}</div>
                <div class="text-sm text-slate-500 mt-1">₦{{ number_format($p->amount_per_hand) }}/hand · {{ (int) $p->hand_count }} hand(s)</div>
                <div class="text-xs text-slate-400 mt-2">{{ $p->month_count }} months · {{ $p->year }}</div>
            </a>
        @empty
            <div class="col-span-full text-sm text-slate-500 bg-white border border-dashed border-slate-300 rounded-xl p-6 text-center">
                You haven't joined any pockets yet. <a href="{{ route('discover') }}" class="text-brand-dark font-medium">Discover one →</a>
            </div>
        @endforelse
    </div>

    {{-- Adashi --}}
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold">My Adashi</h3>
        <a href="{{ route('adashi.create') }}" class="text-sm bg-brand hover:bg-brand-dark text-white rounded-lg px-3 py-1.5">+ New adashi</a>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($adashis as $a)
            <a href="{{ route('adashi.show', $a->id) }}" class="block bg-white rounded-xl border border-slate-200 p-4 hover:shadow-sm hover:border-brand transition">
                <div class="font-semibold truncate">{{ $a->name }}</div>
                <div class="text-sm text-slate-500 mt-1">₦{{ number_format($a->amount_per_cycle) }}/cycle · {{ $a->total_members }} members</div>
                <div class="text-xs text-slate-400 mt-2">Cycle {{ $a->current_cycle_number }} · {{ ucfirst(strtolower($a->status)) }}</div>
            </a>
        @empty
            <div class="col-span-full text-sm text-slate-500 bg-white border border-dashed border-slate-300 rounded-xl p-6 text-center">
                No adashi groups yet. <a href="{{ route('adashi.create') }}" class="text-brand-dark font-medium">Start one →</a>
            </div>
        @endforelse
    </div>
@endsection
