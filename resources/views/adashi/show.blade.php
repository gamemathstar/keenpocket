@extends('layouts.app')
@section('title', $adashi->name)
@section('heading', 'Adashi')

@section('content')
@if (!$myMember && !$isAdmin)
    @include('adashi._public')
@else
    @php
        $showPayout = $isAdmin || $adashi->payout_visible;
        $cycleTarget = (int) $adashi->amount_per_cycle * (int) $adashi->total_members;
        $cyclePct = $currentRecord && $cycleTarget > 0 ? (int) round($currentRecord->total_collected / $cycleTarget * 100) : 0;
        $recv = $currentRecord ? $members->firstWhere('position', $adashi->current_cycle_number) : null;
        $recvMine = $recv && $recv->user_id == auth()->id();
        $recvReveal = $isAdmin || $adashi->payout_visible || $recvMine;
        $membersPaidPct = $currentRecord && (int) $adashi->total_members > 0 ? (int) round($currentRecord->paid_members_count / $adashi->total_members * 100) : 0;
        $totalCollected = $records->sum('total_collected');
        $cyclesDone = $records->where('status', 'COMPLETED')->count();
        $chosenAcct = $myMember ? $myAccounts->firstWhere('id', (int) $myMember->bank_account_id) : null;
    @endphp

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-dark">{{ $adashi->name }}</h2>
            <p class="text-slate-500 text-sm mt-0.5">{{ $adashi->total_members }} members · every {{ $adashi->cycle_duration_days }} days · {{ ucfirst(strtolower($adashi->rotation_mode)) }} rotation · ₦{{ number_format($adashi->amount_per_cycle) }}/cycle@if($isAdmin) · you're the admin@endif</p>
        </div>
        <div class="flex items-center gap-2">
            @if (!$isAdmin && $myMember)
                <x-rate-admin :action="route('adashi.rateAdmin', $adashi->id)" :average="$adminRating" :my="$myRating" />
            @endif
            @if ($isAdmin)
                <a href="{{ route('adashi.members', $adashi->id) }}" class="inline-flex items-center gap-1.5 rounded-xl border-2 border-slate-200 hover:border-brand text-slate-700 font-bold text-sm px-4 py-2">⚙️ Manage</a>
            @endif
            @if ($adashi->is_public ?? false)
                <x-share-card cardTitle="Join my adashi" :cardBig="$adashi->name"
                    :cardSub="'₦'.number_format($adashi->amount_per_cycle).'/cycle · '.$adashi->total_members.' members'"
                    :shareText="'Join my KeenPocket adashi: '.$adashi->name.' (₦'.number_format($adashi->amount_per_cycle).'/cycle).'"
                    :shareUrl="route('adashi.show', $adashi->id)" />
            @endif
        </div>
    </div>

    {{-- 3-column hub --}}
    <div class="grid lg:grid-cols-12 gap-6 mb-6 items-start">
        {{-- LEFT: organiser + payout details + tip --}}
        <div class="lg:col-span-3 space-y-6">
            <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5 text-center">
                <div class="rounded-full ring-4 ring-brand-light inline-block"><x-avatar :user="$admin ?? $adashi->name" :size="72" /></div>
                <div class="mt-2 font-extrabold text-lg">{{ $admin->name ?? 'Organiser' }}</div>
                <div class="text-xs text-brand-dark font-bold uppercase tracking-wide">Master organiser</div>
                <div class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-brand-light px-3 py-1 text-xs font-bold text-brand-dark">
                    <span class="uppercase tracking-wide text-[10px] text-slate-500">Reputation</span>
                    <span>{{ $reputation['band'] ?? 'New' }}</span>
                    @if (!empty($adminRating['count']))<span class="text-amber-500">★ {{ number_format($adminRating['average'], 1) }}</span>@endif
                </div>
                {{-- Payout details (below the organiser) --}}
                @if ($adashi->nuban || $adashi->bank)
                    <div class="mt-4 text-left border-t border-slate-100 pt-3">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1.5">Payout details</div>
                        <div class="flex items-center gap-2 rounded-xl bg-slate-50 p-2.5">
                            <span class="text-lg shrink-0">🏦</span>
                            <div class="min-w-0">
                                <div class="text-sm font-bold truncate">{{ $adashi->bank ?: ($adashi->account_name ?: $adashi->name) }}</div>
                                @if ($adashi->nuban)<div class="text-xs text-slate-500 font-mono">{{ $adashi->nuban }}</div>@endif
                            </div>
                            @if ($adashi->nuban)<button type="button" onclick="kpCopy('{{ $adashi->nuban }}', this)" title="Copy account number" class="ml-auto text-slate-400 hover:text-brand-dark">📋</button>@endif
                        </div>
                    </div>
                @elseif ($isAdmin)
                    <div class="mt-3 text-xs text-amber-600">No collection account yet — <button type="button" onclick="document.getElementById('editAcctModal').classList.remove('hidden')" class="underline font-medium">add one</button>.</div>
                @endif
                <a href="{{ route('users.show', $adashi->admin_id) }}" class="inline-block mt-3 text-xs font-bold text-brand-dark hover:underline">View organiser →</a>
            </div>

            {{-- Mr K tip --}}
            <div class="rounded-[1.5rem] card-depth-brand bg-brand text-white p-5">
                <p class="font-bold leading-snug">"Keep the energy up! {{ $currentRecord->paid_members_count ?? 0 }} of {{ $adashi->total_members }} members have contributed this cycle."</p>
                <p class="text-xs opacity-80 mt-2">— Mr. K</p>
            </div>
        </div>

        {{-- CENTER: weekly savings cycle card --}}
        <div class="lg:col-span-6">
            @if ($currentRecord)
                <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6 relative overflow-hidden">
                    @if ($myMember && $myOwed <= 0)
                        <div class="absolute top-5 right-5 -rotate-[8deg] select-none border-[3px] border-emerald-500 text-emerald-600 font-extrabold uppercase tracking-widest rounded-lg px-3 py-1.5 text-sm opacity-90">Paid ✓</div>
                    @endif
                    <div class="flex items-start justify-between gap-3">
                        <span class="inline-block text-xs font-bold uppercase tracking-wide rounded-full bg-amber-100 text-amber-700 px-3 py-1">Cycle #{{ $currentRecord->cycle_number }} · {{ ucfirst(strtolower($currentRecord->status)) }}</span>
                        <div class="text-right">
                            <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Target</div>
                            <div class="text-lg font-extrabold text-brand-dark">₦{{ number_format($cycleTarget) }}</div>
                        </div>
                    </div>
                    <h3 class="text-2xl font-extrabold mt-3">{{ $adashi->name }}</h3>
                    <div class="text-xs text-slate-400 mt-1">Receiver this cycle: {{ $recv ? ($recvReveal ? $recv->name.($recvMine ? ' (you)' : '') : 'position '.$adashi->current_cycle_number) : '—' }}</div>

                    <div class="flex items-end justify-between mt-5">
                        <div><span class="text-3xl font-extrabold">₦{{ number_format($currentRecord->total_collected) }}</span> <span class="text-slate-400 text-sm">collected</span></div>
                        <div class="text-2xl font-extrabold text-brand-dark">{{ $cyclePct }}%</div>
                    </div>
                    <div class="h-4 rounded-full bg-slate-100 mt-2 overflow-hidden"><div class="h-full bg-brand rounded-full" style="width: {{ min(100, $cyclePct) }}%"></div></div>

                    {{-- Member tiles --}}
                    <div class="flex flex-wrap gap-2 mt-4">
                        @foreach ($members->take(12) as $m)
                            @php $init = strtoupper(mb_substr(trim($m->name), 0, 1)); @endphp
                            <span class="h-9 w-9 rounded-xl flex items-center justify-center text-xs font-bold {{ $m->has_received ? 'bg-emerald-100 text-emerald-700' : 'bg-brand-light text-brand-dark' }}" title="{{ $m->name }}{{ $m->has_received ? ' · received payout' : '' }}">{{ $init ?: '?' }}</span>
                        @endforeach
                        @if ($members->count() > 12)<span class="h-9 w-9 rounded-xl bg-slate-100 text-slate-500 text-[11px] font-bold flex items-center justify-center">+{{ $members->count() - 12 }}</span>@endif
                    </div>

                    @if (($adashi->nuban || $adashi->bank) && $myMember)
                        <div class="text-xs text-slate-400 mt-4">Pay to <span class="font-medium text-slate-600">{{ $adashi->account_name ?: $adashi->name }}</span>@if($adashi->bank) · {{ $adashi->bank }}@endif @if($adashi->nuban)· <span class="font-mono">{{ $adashi->nuban }}</span>@endif</div>
                    @endif
                    @if ($isAdmin && $receiverAccount)
                        <div class="text-xs text-slate-500 mt-2">Send payout to: <span class="font-medium">{{ $receiverAccount->account_name }}</span> · {{ $receiverAccount->bank }} · <span class="font-mono">{{ $receiverAccount->nuban }}</span></div>
                    @endif

                    {{-- Contribute (big) + actions --}}
                    <div class="mt-5 space-y-3">
                        @if ($myMember && $myOwed > 0)
                            <button type="button" onclick="document.getElementById('contribModal').classList.remove('hidden')"
                                    class="w-full rounded-2xl bg-brand hover:bg-brand-dark text-white font-extrabold text-lg py-3.5">🤲 Contribute ₦{{ number_format($myOwed) }}</button>
                        @endif
                        <div class="flex flex-wrap gap-2">
                            @if ($myMember)
                                @if ($myAccounts->isEmpty())
                                    <a href="{{ route('settings') }}" class="btn-soft text-sm">💳 Add payout account</a>
                                @else
                                    <button type="button" onclick="document.getElementById('payoutAcctModal').classList.remove('hidden')" class="btn-soft text-sm">
                                        💳 {{ $chosenAcct ? $chosenAcct->bank.' · '.$chosenAcct->nuban : 'Set payout account' }}
                                    </button>
                                @endif
                            @endif
                            @if ($isAdmin)
                                <button type="button" onclick="document.getElementById('addContribModal').classList.remove('hidden')" class="btn-soft text-sm">+ Add contribution</button>
                                <button type="button" onclick="document.getElementById('editAcctModal').classList.remove('hidden')" class="btn-soft text-sm">Edit collection account</button>
                                <form method="POST" action="{{ route('adashi.reconcile', $adashi->id) }}">
                                    @csrf
                                    <button class="btn-soft text-sm">Reconcile &amp; rotate</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if ($currentRecord->due_at)
                        <div class="text-center text-xs text-slate-400 mt-4 font-bold uppercase tracking-wide">Next payout: {{ \Illuminate\Support\Carbon::parse($currentRecord->due_at)->format('D, j M Y') }}</div>
                    @endif
                </div>
            @else
                <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6 text-center text-slate-500">No active cycle yet.</div>
            @endif
        </div>

        {{-- RIGHT: rotation timeline (+ disputes summary) --}}
        <div class="lg:col-span-3 space-y-6">
            @if ($showPayout)
                <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-extrabold text-xs uppercase tracking-wide text-slate-500">Rotation timeline</h3>
                        @if ($isAdmin)
                            <form method="POST" action="{{ route('adashi.payoutVisibility', $adashi->id) }}">@csrf<button class="text-[11px] rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 px-2 py-0.5" title="{{ $adashi->payout_visible ? 'Order visible to all' : 'Order private' }}">{{ $adashi->payout_visible ? '👀' : '🙈' }}</button></form>
                        @endif
                    </div>
                    <ol class="relative border-l-2 border-slate-200 ml-2 space-y-5">
                        @foreach ($timeline as $t)
                            @php $reveal = $showPayout || $t['is_me']; @endphp
                            <li class="ml-5">
                                <span class="absolute -left-[11px] flex h-5 w-5 items-center justify-center rounded-full ring-4 ring-white
                                    {{ $t['has_received'] ? 'bg-emerald-500' : ($t['is_current'] ? 'bg-brand' : 'bg-slate-300') }}">
                                    @if ($t['has_received'])<span class="text-white text-[10px]">✓</span>@endif
                                </span>
                                <div class="font-bold text-sm">#{{ $t['position'] }} · {{ $reveal ? $t['name'] : 'Member' }}@if ($t['is_me'])<span class="text-xs text-brand-dark font-extrabold"> (you)</span>@endif</div>
                                <div class="text-xs {{ $t['is_current'] ? 'text-brand-dark font-bold' : 'text-slate-400' }}">
                                    @if ($t['has_received'])received @elseif ($t['is_current'])receiving now @else upcoming @endif
                                    @if ($reveal) · ~ {{ $t['payout_date'] }}@endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
            @if ($canDispute && count($disputes))
                <a href="#disputes" class="block bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5 hover:border-brand">
                    <div class="flex items-center justify-between">
                        <h3 class="font-extrabold text-xs uppercase tracking-wide text-slate-500">Disputes</h3>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-600">{{ count($disputes) }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">{{ count($disputes) }} case(s) — open the disputes section below to review.</p>
                </a>
            @endif
        </div>
    </div>

    {{-- Stat chips --}}
    <div class="grid grid-cols-3 gap-3 sm:gap-4 mb-6">
        <div class="rounded-[1.5rem] bg-rose-50 border-2 border-rose-100 p-4">
            <div class="text-[11px] sm:text-xs text-slate-500 font-bold">Group health</div>
            <div class="text-lg sm:text-xl font-extrabold text-rose-600">{{ $membersPaidPct }}% paid up</div>
        </div>
        <div class="rounded-[1.5rem] bg-amber-50 border-2 border-amber-100 p-4">
            <div class="text-[11px] sm:text-xs text-slate-500 font-bold">Total saved</div>
            <div class="text-lg sm:text-xl font-extrabold text-amber-600">₦{{ number_format($totalCollected) }}</div>
        </div>
        <div class="rounded-[1.5rem] bg-sky-50 border-2 border-sky-100 p-4">
            <div class="text-[11px] sm:text-xs text-slate-500 font-bold">Cycles completed</div>
            <div class="text-lg sm:text-xl font-extrabold text-sky-600">{{ $cyclesDone }}</div>
        </div>
    </div>

    {{-- Secondary content: main + sidebar --}}
    <div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">

    {{-- Contributions awaiting verification --}}
    @if ($currentRecord)
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
            <h3 class="font-semibold mb-3">Contributions — cycle {{ $currentRecord->cycle_number }}</h3>
            @php $visiblePending = $isAdmin ? $pending : $pending->where('user_id', auth()->id()); @endphp
            @if ($visiblePending->isNotEmpty())
                <p class="text-xs font-medium text-slate-500 mb-2">Awaiting verification</p>
                <ul class="divide-y divide-slate-100">
                    @foreach ($visiblePending as $p)
                        <li class="py-2 flex items-center justify-between text-sm">
                            <span>{{ $isAdmin ? $p->name : 'You' }} · ₦{{ number_format($p->amount) }}</span>
                            @if ($isAdmin)
                                <span class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('adashi.contribution.verify', $p->id) }}">@csrf<button class="text-xs rounded-md bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1">Verify</button></form>
                                    <form method="POST" action="{{ route('adashi.contribution.decline', $p->id) }}">@csrf<button class="text-xs rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 px-3 py-1">Decline</button></form>
                                </span>
                            @else
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">pending</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-slate-500">No contributions awaiting verification.</p>
            @endif
        </div>
    @endif

    {{-- Group rules + admin tools --}}
    @if ($adashi->rules || $isAdmin)
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold">📋 Group rules</h3>
                @if ($isAdmin)
                    <div class="flex gap-2">
                        <button type="button" onclick="document.getElementById('rulesModal').classList.remove('hidden')" class="btn-soft text-sm">Edit rules</button>
                        <button type="button" onclick="document.getElementById('cloneModal').classList.remove('hidden')" class="btn-soft text-sm">⧉ Clone</button>
                    </div>
                @endif
            </div>
            @if ($adashi->rules)
                <p class="text-sm text-slate-600 whitespace-pre-line">{{ $adashi->rules }}</p>
            @else
                <p class="text-sm text-slate-400">No rules set. Add the group's agreement (contribution dates, penalties, payout order policy, etc.).</p>
            @endif
        </div>
        @if ($isAdmin)
            <div id="rulesModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" onclick="if(event.target===this)this.classList.add('hidden')">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100">
                        <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md" alt="KeenPocket">
                        <span class="font-semibold">Group rules</span>
                        <button type="button" onclick="document.getElementById('rulesModal').classList.add('hidden')" class="ml-auto text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('adashi.rules', $adashi->id) }}" class="p-5 space-y-3">
                        @csrf
                        <textarea name="rules" rows="6" maxlength="5000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="e.g. Contributions due before each cycle's due date. Rotation order is fixed.">{{ $adashi->rules }}</textarea>
                        <button class="w-full rounded-lg bg-brand text-white font-medium py-2.5">Save rules</button>
                    </form>
                </div>
            </div>

            {{-- Clone modal: adjust settings + choose which members to carry over --}}
            <div id="cloneModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" onclick="if(event.target===this)this.classList.add('hidden')">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100 shrink-0">
                        <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md" alt="KeenPocket">
                        <span class="font-semibold">Clone adashi</span>
                        <button type="button" onclick="document.getElementById('cloneModal').classList.add('hidden')" class="ml-auto text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('adashi.clone', $adashi->id) }}" class="p-5 space-y-3 overflow-y-auto">
                        @csrf
                        <input name="name" value="{{ $adashi->name }} (copy)" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Name">
                        <div class="grid grid-cols-3 gap-2">
                            <div><label class="block text-xs font-medium mb-1">₦/cycle</label><input type="number" name="amount_per_cycle" value="{{ $adashi->amount_per_cycle }}" min="1" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                            <div><label class="block text-xs font-medium mb-1">Cycle days</label><input type="number" name="cycle_duration_days" value="{{ $adashi->cycle_duration_days }}" min="1" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                            <div><label class="block text-xs font-medium mb-1">Start</label><input type="date" name="start_date" value="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                        </div>
                        <div>
                            <p class="text-xs font-medium mb-1">Carry over members</p>
                            <div class="max-h-40 overflow-y-auto space-y-1 rounded-lg border border-slate-200 p-2">
                                @foreach ($members->where('is_active', 1) as $m)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="members[]" value="{{ $m->user_id }}" {{ $m->user_id == $adashi->admin_id ? 'checked disabled' : 'checked' }} class="rounded border-slate-300 text-brand focus:ring-brand">
                                        <span>{{ $m->name }}@if($m->user_id == $adashi->admin_id) <span class="text-xs text-slate-400">(you · admin)</span>@endif</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Uncheck anyone you don't want in the new adashi.</p>
                        </div>
                        <button class="w-full rounded-lg bg-brand text-white font-medium py-2.5">Create clone</button>
                    </form>
                </div>
            </div>
        @endif
    @endif

    </div>{{-- /secondary main column --}}

    {{-- Secondary sidebar --}}
    <aside class="space-y-6">
        {{-- Members & rotation order --}}
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold">Members &amp; rotation order</h3>
                @if ($isAdmin)
                    <a href="{{ route('adashi.members', $adashi->id) }}" class="text-sm text-brand-dark hover:underline">Manage →</a>
                @endif
            </div>
            @if ($showPayout)
                <ul class="divide-y divide-slate-100">
                    @foreach ($members as $m)
                        <li class="py-2 flex items-center justify-between text-sm">
                            <span><span class="text-slate-400 mr-2">#{{ $m->position }}</span>{{ $m->name }}</span>
                            <span>@if($m->has_received)<span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">received</span>@else<span class="text-xs text-slate-400">waiting</span>@endif</span>
                        </li>
                    @endforeach
                </ul>
            @else
                {{-- Private: unordered grid of members (no positions, no payout order) --}}
                <div class="grid grid-cols-2 gap-3">
                    @foreach ($members as $m)
                        @php $mine = $m->user_id == auth()->id(); $init = strtoupper(mb_substr(trim($m->name), 0, 1)); @endphp
                        <div class="flex items-center gap-2 rounded-xl border {{ $mine ? 'border-brand bg-brand-light/40' : 'border-slate-200' }} p-2">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand text-white font-bold text-sm shrink-0">{{ $init ?: '?' }}</span>
                            <span class="text-sm font-medium truncate">{{ $mine ? 'You' : $m->name }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Cycles --}}
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
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

        {{-- Top contributors --}}
        <x-mini-leaderboard :rows="$contributors" title="Top contributors" />
    </aside>
    </div>{{-- /hub grid --}}

    @if ($canChat)
        <x-group-chat type="adashi" :id="$adashi->id" :messages="$messages" :canPost="true" />
    @endif

    @if ($canDispute)
        <div id="disputes" class="scroll-mt-24">
            <x-disputes type="adashi" :id="$adashi->id" :disputes="$disputes" :isAdmin="$isAdmin" :canRaise="true" />
        </div>
    @endif

    {{-- Contribute modal --}}
    @if ($myMember && $myOwed > 0)
        <div id="contribModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
             onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100">
                    <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md" alt="KeenPocket">
                    <span class="font-semibold">Contribute</span>
                    <button type="button" onclick="document.getElementById('contribModal').classList.add('hidden')"
                            class="ml-auto text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
                </div>
                <form method="POST" action="{{ route('adashi.contribute', $adashi->id) }}" class="p-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium mb-1">Amount (₦) <span class="text-slate-400 font-normal">· max ₦{{ number_format($myOwed) }}</span></label>
                        <input type="number" name="amount" value="{{ $myOwed }}" min="1" max="{{ $myOwed }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                    </div>
                    <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs px-3 py-2.5 leading-relaxed">
                        Recorded for cycle {{ optional($currentRecord)->cycle_number }} as <strong>pending</strong> until the admin verifies your payment. You can pay at most ₦{{ number_format($myOwed) }} this cycle.
                    </div>
                    <div class="flex gap-2">
                        <button class="flex-1 rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5">Submit contribution</button>
                        <button type="button" onclick="document.getElementById('contribModal').classList.add('hidden')"
                                class="rounded-lg border border-slate-300 px-4 py-2.5 text-slate-600 hover:bg-slate-50">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Payout account modal (member) --}}
    @if ($myMember && $myAccounts->isNotEmpty())
        <div id="payoutAcctModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
             onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100">
                    <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md" alt="KeenPocket">
                    <span class="font-semibold">Your payout account</span>
                    <button type="button" onclick="document.getElementById('payoutAcctModal').classList.add('hidden')" class="ml-auto text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
                </div>
                <form method="POST" action="{{ route('adashi.setAccount', $adashi->id) }}" class="p-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium mb-1">Where you'll receive your payout</label>
                        <select name="bank_account_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                            <option value="">— choose account —</option>
                            @foreach ($myAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ (int) $myMember->bank_account_id === $acc->id ? 'selected' : '' }}>{{ $acc->account_name }} · {{ $acc->bank }} · {{ $acc->nuban }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="text-xs text-slate-400">Manage your saved accounts in <a href="{{ route('settings') }}" class="text-brand-dark hover:underline">settings</a>.</p>
                    <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5">Save account</button>
                </form>
            </div>
        </div>
    @endif

    @if ($isAdmin)
        {{-- Edit collection account modal (admin) --}}
        <div id="editAcctModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
             onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100">
                    <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md" alt="KeenPocket">
                    <span class="font-semibold">Collection account</span>
                    <button type="button" onclick="document.getElementById('editAcctModal').classList.add('hidden')" class="ml-auto text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
                </div>
                <form method="POST" action="{{ route('adashi.bank', $adashi->id) }}" class="p-5 space-y-3">
                    @csrf
                    <input name="account_name" value="{{ $adashi->account_name }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Account name">
                    <input name="bank" value="{{ $adashi->bank }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Bank">
                    <input name="nuban" value="{{ $adashi->nuban }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Account number">
                    <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5">Save account</button>
                </form>
            </div>
        </div>

        {{-- Add contribution for a member modal (admin) --}}
        @if ($currentRecord)
            <div id="addContribModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
                 onclick="if(event.target===this)this.classList.add('hidden')">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100">
                        <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md" alt="KeenPocket">
                        <span class="font-semibold">Add a contribution</span>
                        <button type="button" onclick="document.getElementById('addContribModal').classList.add('hidden')" class="ml-auto text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('adashi.contribution.add', $adashi->id) }}" class="p-5 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium mb-1">Member</label>
                            <select name="member_user_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                                @foreach ($members->where('is_active', 1) as $m)
                                    <option value="{{ $m->user_id }}">#{{ $m->position }} {{ $m->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Amount (₦)</label>
                            <input type="number" name="amount" value="{{ $adashi->amount_per_cycle }}" min="1" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                        </div>
                        <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs px-3 py-2.5">
                            Added as <strong>pending</strong> — verify it below to count it toward the cycle.
                        </div>
                        <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5">Add contribution</button>
                    </form>
                </div>
            </div>
        @endif
    @endif
@endif
@endsection
