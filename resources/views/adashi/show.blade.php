@extends('layouts.app')
@section('title', $adashi->name)
@section('heading', 'Adashi')

@section('content')
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6 flex items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold">{{ $adashi->name }}</h2>
            <p class="text-slate-500 text-sm mt-1">{{ $adashi->total_members }} members · every {{ $adashi->cycle_duration_days }} days · {{ ucfirst(strtolower($adashi->rotation_mode)) }} rotation</p>
            <p class="text-xs text-slate-400 mt-2">Cycle {{ $adashi->current_cycle_number }} · {{ ucfirst(strtolower($adashi->status)) }}@if($isAdmin) · You are the admin @endif</p>
        </div>
        <div class="text-right">
            <div class="text-2xl font-semibold">₦{{ number_format($adashi->amount_per_cycle) }}</div>
            <div class="text-xs text-slate-400">per cycle</div>
        </div>
    </div>

    {{-- Current cycle + actions --}}
    @if ($currentRecord)
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="text-sm text-slate-500">Current cycle {{ $currentRecord->cycle_number }} · {{ ucfirst(strtolower($currentRecord->status)) }}</div>
                    <div class="text-lg font-semibold mt-0.5">Collected ₦{{ number_format($currentRecord->total_collected) }} · {{ $currentRecord->paid_members_count }}/{{ $adashi->total_members }} paid</div>
                    <div class="text-xs text-slate-400 mt-0.5">Receiver this cycle: member #{{ optional($members->firstWhere('position', $adashi->current_cycle_number))->name ?? '—' }}</div>
                </div>
                <div class="flex items-end gap-2">
                    @if ($myMember)
                        <form method="POST" action="{{ route('adashi.contribute', $adashi->id) }}" class="flex items-end gap-2">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium mb-1">My contribution (₦)</label>
                                <input type="number" name="amount" value="{{ $adashi->amount_per_cycle }}" min="1" class="w-32 rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                            </div>
                            <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-4 py-2">Contribute</button>
                        </form>
                    @endif
                    @if ($isAdmin)
                        <form method="POST" action="{{ route('adashi.reconcile', $adashi->id) }}">
                            @csrf
                            <button class="rounded-lg border border-slate-300 hover:bg-slate-50 px-4 py-2 text-sm">Reconcile &amp; rotate</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold mb-3">Members &amp; rotation order</h3>
            <ul class="divide-y divide-slate-100">
                @foreach ($members as $m)
                    <li class="py-2 flex items-center justify-between text-sm">
                        <span><span class="text-slate-400 mr-2">#{{ $m->position }}</span>{{ $m->name }}</span>
                        <span>@if($m->has_received)<span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">received</span>@else<span class="text-xs text-slate-400">waiting</span>@endif</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold mb-3">Cycles</h3>
            <ul class="divide-y divide-slate-100">
                @forelse ($records as $r)
                    <li class="py-2 flex items-center justify-between text-sm">
                        <span>Cycle {{ $r->cycle_number }}</span>
                        <span class="flex items-center gap-2">
                            <span class="text-slate-500">₦{{ number_format($r->total_collected) }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ ucfirst(strtolower($r->status)) }}</span>
                        </span>
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-500">No cycles yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
