@extends('layouts.app')
@section('title', $adashi->name)
@section('heading', 'Adashi')

@section('content')
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6 flex items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold">{{ $adashi->name }}</h2>
            <p class="text-slate-500 text-sm mt-1">{{ $adashi->total_members }} members · every {{ $adashi->cycle_duration_days }} days · {{ ucfirst(strtolower($adashi->rotation_mode)) }} rotation</p>
            <p class="text-xs text-slate-400 mt-2">Cycle {{ $adashi->current_cycle_number }} · {{ ucfirst(strtolower($adashi->status)) }}@if($isAdmin) · You are the admin @endif</p>
            <a href="{{ route('users.show', $adashi->admin_id) }}" class="text-xs text-brand-dark hover:underline">View organiser →</a>
        </div>
        <div class="text-right">
            <div class="text-2xl font-semibold">₦{{ number_format($adashi->amount_per_cycle) }}</div>
            <div class="text-xs text-slate-400">per cycle</div>
        </div>
    </div>

    {{-- Collection account --}}
    @if ($adashi->nuban || $adashi->bank || $isAdmin)
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
            <div class="flex items-center justify-between">
                <div class="text-sm">
                    <span class="text-slate-400">Pay contributions to:</span>
                    @if ($adashi->nuban || $adashi->bank)
                        <span class="font-medium">{{ $adashi->account_name ?: $adashi->name }}</span>
                        @if ($adashi->bank) · {{ $adashi->bank }}@endif
                        @if ($adashi->nuban) · <span class="font-mono">{{ $adashi->nuban }}</span>@endif
                    @else
                        <span class="text-slate-400">not set yet</span>
                    @endif
                </div>
                @if ($isAdmin)
                    <details>
                        <summary class="text-sm text-brand-dark hover:underline cursor-pointer list-none">Edit account</summary>
                        <form method="POST" action="{{ route('adashi.bank', $adashi->id) }}" class="mt-3 grid sm:grid-cols-3 gap-2">
                            @csrf
                            <input name="account_name" value="{{ $adashi->account_name }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Account name">
                            <input name="bank" value="{{ $adashi->bank }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Bank">
                            <input name="nuban" value="{{ $adashi->nuban }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Account number">
                            <button class="sm:col-span-3 rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-medium px-4 py-2">Save account</button>
                        </form>
                    </details>
                @endif
            </div>
        </div>
    @endif

    {{-- Current cycle + actions --}}
    @if ($currentRecord)
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
            @php
                $cycleTarget = (int) $adashi->amount_per_cycle * (int) $adashi->total_members;
                $cyclePct = $cycleTarget > 0 ? (int) round($currentRecord->total_collected / $cycleTarget * 100) : 0;
            @endphp
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-[240px] flex-1">
                    <div class="text-sm text-slate-500">Current cycle {{ $currentRecord->cycle_number }} · {{ ucfirst(strtolower($currentRecord->status)) }}</div>
                    <div class="text-lg font-semibold mt-0.5">{{ $currentRecord->paid_members_count }}/{{ $adashi->total_members }} members paid</div>
                    <div class="text-xs text-slate-400 mt-0.5 mb-2">Receiver this cycle: member #{{ optional($members->firstWhere('position', $adashi->current_cycle_number))->name ?? '—' }}</div>
                    <x-progress-bar :percent="$cyclePct" label="Cycle collected" :current="(int) $currentRecord->total_collected" :target="$cycleTarget" />
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
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold">Members &amp; rotation order</h3>
                @if ($isAdmin)
                    <a href="{{ route('adashi.members', $adashi->id) }}" class="text-sm text-brand-dark hover:underline">Manage →</a>
                @endif
            </div>
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

    {{-- Payout timeline --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
        <h3 class="font-semibold mb-4">🗓️ Payout timeline</h3>
        <ol class="relative border-l-2 border-slate-200 ml-3 space-y-5">
            @foreach ($timeline as $t)
                <li class="ml-6">
                    <span class="absolute -left-[11px] flex h-5 w-5 items-center justify-center rounded-full ring-4 ring-white
                        {{ $t['has_received'] ? 'bg-emerald-500' : ($t['is_current'] ? 'bg-brand' : 'bg-slate-300') }}">
                        @if ($t['has_received'])<span class="text-white text-[10px]">✓</span>@endif
                    </span>
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="font-bold text-sm">
                                #{{ $t['position'] }} · {{ $t['name'] }}
                                @if ($t['is_me'])<span class="text-xs text-brand-dark font-extrabold">(you)</span>@endif
                            </div>
                            <div class="text-xs text-slate-400">~ {{ $t['payout_date'] }}</div>
                        </div>
                        @if ($t['has_received'])
                            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-bold">received</span>
                        @elseif ($t['is_current'])
                            <span class="text-xs px-2 py-0.5 rounded-full bg-brand-light text-brand-dark font-bold">up next</span>
                        @else
                            <span class="text-xs text-slate-400 font-bold uppercase">upcoming</span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>

    <div class="mt-6 max-w-xl">
        <x-mini-leaderboard :rows="$contributors" title="Top contributors" />
    </div>
@endsection
