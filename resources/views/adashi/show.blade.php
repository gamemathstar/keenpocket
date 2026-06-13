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
            @if ($isAdmin)
                @if ($adminRating['count'])
                    <div class="mt-2 text-xs text-slate-500">⭐ {{ number_format($adminRating['average'], 1) }} ({{ $adminRating['count'] }})</div>
                @endif
            @elseif ($myMember)
                <div class="mt-2">
                    <x-rate-admin :action="route('adashi.rateAdmin', $adashi->id)" :average="$adminRating" :my="$myRating" />
                </div>
            @endif
            @if ($adashi->is_public ?? false)
                <div class="mt-2">
                    <x-share-card cardTitle="Join my adashi" :cardBig="$adashi->name"
                        :cardSub="'₦'.number_format($adashi->amount_per_cycle).'/cycle · '.$adashi->total_members.' members'"
                        :shareText="'Join my KeenPocket adashi: '.$adashi->name.' (₦'.number_format($adashi->amount_per_cycle).'/cycle).'"
                        :shareUrl="route('adashi.show', $adashi->id)" />
                </div>
            @endif
        </div>
    </div>

    {{-- Current cycle + actions --}}
    @if ($currentRecord)
        @php
            $cycleTarget = (int) $adashi->amount_per_cycle * (int) $adashi->total_members;
            $cyclePct = $cycleTarget > 0 ? (int) round($currentRecord->total_collected / $cycleTarget * 100) : 0;
            $recv = $members->firstWhere('position', $adashi->current_cycle_number);
            $recvMine = $recv && $recv->user_id == auth()->id();
            $recvReveal = $isAdmin || $adashi->payout_visible || $recvMine;
        @endphp
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6 relative overflow-hidden">
            {{-- "Paid" stamp once the member has covered their share --}}
            @if ($myMember && $myOwed <= 0)
                <div class="absolute top-5 right-5 -rotate-[8deg] select-none border-[3px] border-emerald-500 text-emerald-600 font-extrabold uppercase tracking-widest rounded-lg px-3 py-1.5 text-sm opacity-90">Paid ✓</div>
            @endif

            <div class="text-sm text-slate-500">Current cycle {{ $currentRecord->cycle_number }} · {{ ucfirst(strtolower($currentRecord->status)) }}</div>
            <div class="text-lg font-semibold mt-0.5">{{ $currentRecord->paid_members_count }}/{{ $adashi->total_members }} members paid</div>
            <div class="text-xs text-slate-400 mt-0.5">Receiver this cycle: {{ $recv ? ($recvReveal ? $recv->name.($recvMine ? ' (you)' : '') : 'position '.$adashi->current_cycle_number) : '—' }}</div>
            @if ($isAdmin && $receiverAccount)
                <div class="text-xs text-slate-500">Send to: <span class="font-medium">{{ $receiverAccount->account_name }}</span> · {{ $receiverAccount->bank }} · <span class="font-mono">{{ $receiverAccount->nuban }}</span></div>
            @endif

            {{-- Where members pay, with a copy button --}}
            @if ($adashi->nuban || $adashi->bank)
                <div class="flex items-center flex-wrap gap-1.5 text-sm mt-2">
                    <span class="text-slate-400">Pay to:</span>
                    <span class="font-medium">{{ $adashi->account_name ?: $adashi->name }}</span>
                    @if ($adashi->bank)<span class="text-slate-400">· {{ $adashi->bank }}</span>@endif
                    @if ($adashi->nuban)
                        <span class="text-slate-400">·</span><span class="font-mono">{{ $adashi->nuban }}</span>
                        <button type="button" onclick="kpCopy('{{ $adashi->nuban }}', this)" title="Copy account number" class="text-base text-slate-400 hover:text-brand-dark leading-none">📋</button>
                    @endif
                </div>
            @elseif ($isAdmin)
                <div class="text-sm text-amber-600 mt-2">No collection account set yet — add one so members know where to pay.</div>
            @endif

            <div class="mt-4">
                <x-progress-bar :percent="$cyclePct" label="Cycle collected" :current="(int) $currentRecord->total_collected" :target="$cycleTarget" />
            </div>

            @php $chosenAcct = $myMember ? $myAccounts->firstWhere('id', (int) $myMember->bank_account_id) : null; @endphp
            <div class="mt-4 flex flex-wrap gap-2">
                @if ($myMember && $myOwed > 0)
                    <button type="button" onclick="document.getElementById('contribModal').classList.remove('hidden')"
                            class="rounded-lg bg-brand text-white px-5 py-2.5">Contribute</button>
                @endif
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
    @endif

    {{-- Contributions awaiting verification --}}
    @if ($currentRecord)
        <div class="bg-white rounded-xl border border-slate-200 p-5 mt-6">
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

    <div class="grid lg:grid-cols-2 gap-6 mt-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold">Members &amp; rotation order</h3>
                @if ($isAdmin)
                    <a href="{{ route('adashi.members', $adashi->id) }}" class="text-sm text-brand-dark hover:underline">Manage →</a>
                @endif
            </div>
            @php $showPayout = $isAdmin || $adashi->payout_visible; @endphp
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
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
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

    {{-- Payout timeline (the rotation order) — only when visible to the viewer --}}
    @if ($showPayout)
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold">🗓️ Payout timeline</h3>
            @if ($isAdmin)
                <form method="POST" action="{{ route('adashi.payoutVisibility', $adashi->id) }}">
                    @csrf
                    <button class="text-xs rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 px-2.5 py-1">
                        {{ $adashi->payout_visible ? '👀 Order: visible to all' : '🙈 Order: private' }}
                    </button>
                </form>
            @endif
        </div>
        <ol class="relative border-l-2 border-slate-200 ml-3 space-y-5">
            @foreach ($timeline as $t)
                @php $reveal = $showPayout || $t['is_me']; @endphp
                <li class="ml-6">
                    <span class="absolute -left-[11px] flex h-5 w-5 items-center justify-center rounded-full ring-4 ring-white
                        {{ $t['has_received'] ? 'bg-emerald-500' : ($t['is_current'] ? 'bg-brand' : 'bg-slate-300') }}">
                        @if ($t['has_received'])<span class="text-white text-[10px]">✓</span>@endif
                    </span>
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="font-bold text-sm">
                                #{{ $t['position'] }} · {{ $reveal ? $t['name'] : 'Member' }}
                                @if ($t['is_me'])<span class="text-xs text-brand-dark font-extrabold">(you)</span>@endif
                            </div>
                            <div class="text-xs text-slate-400">@if ($reveal)~ {{ $t['payout_date'] }}@else position {{ $t['position'] }}@endif</div>
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
    @endif

    <div class="mt-6 max-w-xl">
        <x-mini-leaderboard :rows="$contributors" title="Top contributors" />
    </div>

    @if ($canChat)
        <x-group-chat type="adashi" :id="$adashi->id" :messages="$messages" :canPost="true" />
    @endif

    @if ($canDispute)
        <x-disputes type="adashi" :id="$adashi->id" :disputes="$disputes" :isAdmin="$isAdmin" :canRaise="true" />
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
@endsection
