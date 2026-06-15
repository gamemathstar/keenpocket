@extends('layouts.app')
@section('title', $pocket->title)
@section('heading', 'Pocket')

@section('content')
@if (!$isMember && !$isOwner)
    @include('pockets._public')
@else
    @php
        $activeMembers = $members->where('status', 1)->count();
        $statusLabel = $pocket->status ? 'Open' : 'Closed';
    @endphp

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-dark">{{ $pocket->title }}</h2>
            <p class="text-slate-500 text-sm mt-0.5">{{ $pocket->description ?: 'Community savings pocket.' }} · {{ $pocket->month_count }} months · {{ $pocket->year }} · ₦{{ number_format($pocket->amount_per_hand) }}/hand @if($isOwner)· you're the admin @endif</p>
        </div>
        <div class="flex items-center gap-2">
            @if (!$isOwner && $isMember)
                <x-rate-admin :action="route('pockets.rateAdmin', $pocket->id)" :average="$adminRating" :my="$myRating" />
            @endif
            @if ($isOwner)
                <a href="{{ route('pockets.manage', $pocket->id) }}" class="inline-flex items-center gap-1.5 rounded-xl border-2 border-slate-200 hover:border-brand text-slate-700 font-bold text-sm px-4 py-2">⚙️ Manage</a>
            @endif
            <x-share-card cardTitle="Join my pocket" :cardBig="$pocket->title"
                :cardSub="'₦'.number_format($pocket->amount_per_hand).'/hand · '.$pocket->month_count.' months'"
                :shareText="'Join my KeenPocket savings pocket: '.$pocket->title.' (₦'.number_format($pocket->amount_per_hand).'/hand).'"
                :shareUrl="route('pockets.show', $pocket->id)" />
        </div>
    </div>

    {{-- Stat chips --}}
    <div class="grid grid-cols-3 gap-3 sm:gap-4 mb-6">
        <div class="rounded-[1.5rem] bg-rose-50 border-2 border-rose-100 p-4">
            <div class="text-[11px] sm:text-xs text-slate-500 font-bold">My goal</div>
            <div class="text-lg sm:text-xl font-extrabold text-rose-600">{{ $isMember && $target > 0 ? $progress.'%' : '—' }}</div>
        </div>
        <div class="rounded-[1.5rem] bg-amber-50 border-2 border-amber-100 p-4">
            <div class="text-[11px] sm:text-xs text-slate-500 font-bold">Members</div>
            <div class="text-lg sm:text-xl font-extrabold text-amber-600">{{ $activeMembers }}</div>
        </div>
        <div class="rounded-[1.5rem] bg-sky-50 border-2 border-sky-100 p-4">
            <div class="text-[11px] sm:text-xs text-slate-500 font-bold">Per hand</div>
            <div class="text-lg sm:text-xl font-extrabold text-sky-600">₦{{ number_format($pocket->amount_per_hand) }}</div>
        </div>
    </div>

    {{-- 3-column hub --}}
    <div class="grid lg:grid-cols-12 gap-6 mb-6 items-start">
        {{-- LEFT: organiser + payout + tip --}}
        <div class="lg:col-span-3 space-y-6">
            <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5 text-center">
                <div class="rounded-full ring-4 ring-brand-light inline-block"><x-avatar :user="$owner ?? $pocket->title" :size="72" /></div>
                <div class="mt-2 font-extrabold text-lg">{{ optional($owner)->name ?? 'Organiser' }}</div>
                <div class="text-xs text-brand-dark font-bold uppercase tracking-wide">Organiser</div>
                <div class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-brand-light px-3 py-1 text-xs font-bold text-brand-dark">
                    <span class="uppercase tracking-wide text-[10px] text-slate-500">Reputation</span>
                    <span>{{ $reputation['band'] ?? 'New' }}</span>
                    @if (!empty($adminRating['count']))<span class="text-amber-500">★ {{ number_format($adminRating['average'], 1) }}</span>@endif
                </div>
                @if ($pocket->nuban || $pocket->bank)
                    <div class="mt-4 text-left border-t border-slate-100 pt-3">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1.5">Pay contributions to</div>
                        <div class="flex items-center gap-2 rounded-xl bg-slate-50 p-2.5">
                            <span class="text-lg shrink-0">🏦</span>
                            <div class="min-w-0">
                                <div class="text-sm font-bold truncate">{{ $pocket->bank ?: ($pocket->account_name ?: $pocket->title) }}</div>
                                @if ($pocket->nuban)<div class="text-xs text-slate-500 font-mono">{{ $pocket->nuban }}</div>@endif
                            </div>
                            @if ($pocket->nuban)<button type="button" onclick="kpCopy('{{ $pocket->nuban }}', this)" title="Copy account number" class="ml-auto text-slate-400 hover:text-brand-dark">📋</button>@endif
                        </div>
                    </div>
                @endif
                @if ($isOwner)
                    <a href="{{ route('pockets.manage', $pocket->id) }}" class="inline-block mt-3 text-xs font-bold text-brand-dark hover:underline">Manage pocket →</a>
                @elseif ($owner)
                    <a href="{{ route('users.show', $owner->id) }}" class="inline-block mt-3 text-xs font-bold text-brand-dark hover:underline">View organiser →</a>
                @endif
            </div>

            {{-- Mr K tip --}}
            <div class="rounded-[1.5rem] card-depth-brand bg-brand text-white p-5">
                <p class="font-bold leading-snug">"{{ $activeMembers }} member{{ $activeMembers === 1 ? '' : 's' }} saving together. Pay on time and watch your goal fill up!"</p>
                <p class="text-xs opacity-80 mt-2">— Mr. K</p>
            </div>
        </div>

        {{-- CENTER: contribution card --}}
        <div class="lg:col-span-6">
            <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6 relative overflow-hidden">
                @if ($isMember && $target > 0 && $contributed >= $target)
                    <div class="absolute top-5 right-5 -rotate-[8deg] select-none border-[3px] border-emerald-500 text-emerald-600 font-extrabold uppercase tracking-widest rounded-lg px-3 py-1.5 text-sm opacity-90">Done ✓</div>
                @endif
                <div class="flex items-start justify-between gap-3">
                    <span class="inline-block text-xs font-bold uppercase tracking-wide rounded-full {{ $pocket->status ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }} px-3 py-1">{{ $statusLabel }}</span>
                    <div class="text-right">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ $isMember && $target > 0 ? 'My target' : 'Per hand' }}</div>
                        <div class="text-lg font-extrabold text-brand-dark">₦{{ number_format($isMember && $target > 0 ? $target : $pocket->amount_per_hand) }}</div>
                    </div>
                </div>
                <h3 class="text-2xl font-extrabold mt-3">{{ $pocket->title }}</h3>
                <p class="text-xs text-slate-400 mt-1">{{ $pocket->month_count }} months · {{ $pocket->year }} @if(!is_null($handsLeft))· {{ $handsLeft }} hand(s) left @endif</p>

                @if ($isMember && $target > 0)
                    <div class="flex items-end justify-between mt-5">
                        <div><span class="text-3xl font-extrabold">₦{{ number_format($contributed) }}</span> <span class="text-slate-400 text-sm">contributed</span></div>
                        <div class="text-2xl font-extrabold text-brand-dark">{{ $progress }}%</div>
                    </div>
                    <div class="h-4 rounded-full bg-slate-100 mt-2 overflow-hidden"><div class="h-full bg-brand rounded-full" style="width: {{ min(100, $progress) }}%"></div></div>
                @endif

                {{-- member tiles --}}
                <div class="flex flex-wrap gap-2 mt-4">
                    @foreach ($members->where('status', 1)->take(12) as $m)
                        @php $init = strtoupper(mb_substr(trim($m->name), 0, 1)); @endphp
                        <span class="h-9 w-9 rounded-xl bg-brand-light text-brand-dark flex items-center justify-center text-xs font-bold" title="{{ $m->name }}">{{ $init ?: '?' }}</span>
                    @endforeach
                    @if ($activeMembers > 12)<span class="h-9 w-9 rounded-xl bg-slate-100 text-slate-500 text-[11px] font-bold flex items-center justify-center">+{{ $activeMembers - 12 }}</span>@endif
                </div>

                @if ($isMember && ($pocket->nuban || $pocket->bank))
                    <div class="text-xs text-slate-400 mt-4">Pay to <span class="font-medium text-slate-600">{{ $pocket->account_name ?: $pocket->title }}</span>@if($pocket->bank) · {{ $pocket->bank }}@endif @if($pocket->nuban)· <span class="font-mono">{{ $pocket->nuban }}</span>@endif</div>
                @endif

                @if ($isMember)
                    <a href="{{ route('invoices.create', $pocket->id) }}" class="block text-center mt-5 w-full rounded-2xl bg-brand hover:bg-brand-dark text-white font-extrabold text-lg py-3.5">🤲 Record a contribution</a>
                @elseif ($isOwner)
                    <a href="{{ route('pockets.manage', $pocket->id) }}" class="block text-center mt-5 w-full rounded-2xl bg-brand hover:bg-brand-dark text-white font-extrabold text-lg py-3.5">Manage pocket</a>
                @endif
            </div>
        </div>

        {{-- RIGHT: approvals + disputes summary --}}
        <div class="lg:col-span-3 space-y-6">
            @if ($isOwner)
                <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
                    <h3 class="font-extrabold text-sm uppercase tracking-wide text-slate-500 mb-3">Payments to approve
                        @if ($pendingApprovals->isNotEmpty())<span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ $pendingApprovals->count() }}</span>@endif
                    </h3>
                    @forelse ($pendingApprovals as $inv)
                        <div class="py-2 border-b border-slate-100 last:border-0 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="font-medium truncate">{{ $inv->name }}</span>
                                <span class="font-bold">₦{{ number_format($inv->amount) }}</span>
                            </div>
                            <div class="flex gap-2 mt-1.5">
                                <form method="POST" action="{{ route('invoices.markPaid', $inv->id) }}">@csrf<button class="text-xs rounded-md bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1">Mark paid</button></form>
                                <form method="POST" action="{{ route('invoices.decline', $inv->id) }}" onsubmit="return confirm('Decline this payment request?')">@csrf<button class="text-xs rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 px-3 py-1">Decline</button></form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No payments awaiting approval. 🎉</p>
                    @endforelse
                </div>
            @endif

            {{-- My progress: contributed + donations --}}
            @if (($isMember && $target > 0) || $charity)
                <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
                    <h3 class="font-extrabold text-sm uppercase tracking-wide text-slate-500 mb-4">My progress</h3>
                    <div class="flex flex-wrap justify-around gap-4">
                        @if ($isMember && $target > 0)
                            <div class="text-center">
                                <x-progress-ring :percent="$progress" :label="$progress.'%'" sublabel="contributed" :size="92" />
                                <div class="text-xs text-slate-400 mt-1">₦{{ number_format($contributed) }} / ₦{{ number_format($target) }}</div>
                            </div>
                        @endif
                        @if ($charity)
                            @php $dpct = (int) ($charity['percent'] ?? 0); @endphp
                            <div class="text-center">
                                <x-progress-ring :percent="$dpct" :label="$dpct.'%'" sublabel="donations" :size="92" color="#f59e0b" />
                                <div class="text-xs text-slate-400 mt-1">₦{{ number_format($charity['raised']) }} @if(!empty($charity['target_amount']))/ ₦{{ number_format($charity['target_amount']) }} @endif</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($canDispute && count($disputes))
                <a href="#disputes" class="block bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5 hover:border-brand">
                    <div class="flex items-center justify-between">
                        <h3 class="font-extrabold text-sm uppercase tracking-wide text-slate-500">Disputes</h3>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-600">{{ count($disputes) }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">{{ count($disputes) }} case(s) — open the disputes section below.</p>
                </a>
            @endif
        </div>
    </div>

    {{-- Secondary content: main + sidebar --}}
    <div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">

    {{-- Group rules + owner tools --}}
    @if ($pocket->rules || $isOwner)
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold">📋 Group rules</h3>
                @if ($isOwner)
                    <div class="flex gap-2">
                        <button type="button" onclick="document.getElementById('rulesModal').classList.remove('hidden')" class="btn-soft text-sm">Edit rules</button>
                        <button type="button" onclick="document.getElementById('cloneModal').classList.remove('hidden')" class="btn-soft text-sm">⧉ Clone</button>
                    </div>
                @endif
            </div>
            @if ($pocket->rules)
                <p class="text-sm text-slate-600 whitespace-pre-line">{{ $pocket->rules }}</p>
            @else
                <p class="text-sm text-slate-400">No rules set. Add the group's agreement (contribution dates, penalties for lateness, etc.).</p>
            @endif
        </div>
        @if ($isOwner)
            <div id="rulesModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" onclick="if(event.target===this)this.classList.add('hidden')">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100">
                        <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md" alt="KeenPocket">
                        <span class="font-semibold">Group rules</span>
                        <button type="button" onclick="document.getElementById('rulesModal').classList.add('hidden')" class="ml-auto text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('pockets.rules', $pocket->id) }}" class="p-5 space-y-3">
                        @csrf
                        <textarea name="rules" rows="6" maxlength="5000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="e.g. Contributions due by the 5th. Two missed payments → review by the group.">{{ $pocket->rules }}</textarea>
                        <button class="w-full rounded-lg bg-brand text-white font-medium py-2.5">Save rules</button>
                    </form>
                </div>
            </div>

            {{-- Clone modal: adjust settings + choose which members to carry over --}}
            <div id="cloneModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" onclick="if(event.target===this)this.classList.add('hidden')">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100 shrink-0">
                        <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md" alt="KeenPocket">
                        <span class="font-semibold">Clone pocket</span>
                        <button type="button" onclick="document.getElementById('cloneModal').classList.add('hidden')" class="ml-auto text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('pockets.clone', $pocket->id) }}" class="p-5 space-y-3 overflow-y-auto">
                        @csrf
                        <input name="title" value="{{ $pocket->title }} (copy)" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Title">
                        <div class="grid grid-cols-3 gap-2">
                            <div><label class="block text-xs font-medium mb-1">Year</label><input type="number" name="year" value="{{ (int) date('Y') }}" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                            <div><label class="block text-xs font-medium mb-1">₦/hand</label><input type="number" name="amount_per_hand" value="{{ $pocket->amount_per_hand }}" min="1" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                            <div><label class="block text-xs font-medium mb-1">Months</label><input type="number" name="month_count" value="{{ $pocket->month_count }}" min="1" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                        </div>
                        <div><label class="block text-xs font-medium mb-1">Max members (0 = ∞)</label><input type="number" name="max_keens" value="{{ $pocket->max_keens }}" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
                        <div>
                            <p class="text-xs font-medium mb-1">Carry over members</p>
                            <div class="max-h-40 overflow-y-auto space-y-1 rounded-lg border border-slate-200 p-2">
                                @forelse ($members->where('status', 1) as $m)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="members[]" value="{{ $m->user_id }}" checked class="rounded border-slate-300 text-brand focus:ring-brand">
                                        <span>{{ $m->name }} <span class="text-xs text-slate-400">· {{ (int) $m->hand_count }} hand(s)</span></span>
                                    </label>
                                @empty
                                    <p class="text-xs text-slate-400">No active members.</p>
                                @endforelse
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Uncheck anyone you don't want in the new pocket.</p>
                        </div>
                        <button class="w-full rounded-lg bg-brand text-white font-medium py-2.5">Create clone</button>
                    </form>
                </div>
            </div>
        @endif
    @endif

    {{-- My invoices --}}
    <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold">My invoices</h3>
            @if ($isMember)
                <a href="{{ route('invoices.create', $pocket->id) }}" class="text-sm bg-brand hover:bg-brand-dark text-white rounded-lg px-3 py-1.5">+ Contribution</a>
            @endif
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($invoices as $inv)
                <li class="py-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">{{ $inv->invoice_no }}</span>
                        <span class="flex items-center gap-2">
                            <span>₦{{ number_format($inv->amount) }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $inv->payment_status === 'Paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $inv->payment_status }}</span>
                        </span>
                    </div>
                    @if ($inv->payment_status !== 'Paid')
                        <div class="flex gap-2 mt-2">
                            @if ($walletEnabled)
                                <form method="POST" action="{{ route('invoices.payWallet', $inv->id) }}">
                                    @csrf
                                    <button class="text-xs rounded-md bg-brand hover:bg-brand-dark text-white px-3 py-1">Pay from wallet</button>
                                </form>
                            @endif
                            @if ($isOwner)
                                <form method="POST" action="{{ route('invoices.markPaid', $inv->id) }}">
                                    @csrf
                                    <button class="text-xs rounded-md border border-slate-300 hover:bg-slate-50 px-3 py-1">Mark as paid</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </li>
            @empty
                <li class="py-2 text-sm text-slate-500">No invoices yet.</li>
            @endforelse
        </ul>
    </div>

    {{-- Shopping list (group buying) --}}
    @php $canSuggest = $isOwner || ($pocket->open_purchasing_item && $isMember); @endphp
    <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold">🛒 Shopping list</h3>
            <span class="flex items-center gap-2">
                <span class="text-xs px-2 py-0.5 rounded-full {{ $pocket->open_purchasing_item ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $pocket->open_purchasing_item ? 'Suggestions open' : 'Suggestions closed' }}</span>
                @if ($isOwner)
                    <form method="POST" action="{{ route('pockets.selection', $pocket->id) }}">@csrf<button class="text-xs text-brand-dark hover:underline">{{ $pocket->open_purchasing_item ? 'close' : 'open' }}</button></form>
                @endif
            </span>
        </div>
        @if ($shoppingItems->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-slate-400 text-xs">
                        <tr class="text-left border-b border-slate-100">
                            <th class="py-2">Item</th><th>Unit price</th><th>People</th><th>Total</th>
                            @if ($isOwner)<th></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($shoppingItems as $item)
                            <tr>
                                <td class="py-2">{{ $item->name }} @if($item->category)<span class="text-xs text-slate-400">· {{ $item->category }}</span>@endif</td>
                                <td>₦{{ number_format($item->unit_price) }}</td>
                                <td>{{ $item->person_count }}</td>
                                <td class="font-medium">₦{{ number_format($item->unit_price * $item->person_count) }}</td>
                                @if ($isOwner)
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('shopping.destroy', $item->id) }}">@csrf<button class="text-xs text-red-500 hover:underline">remove</button></form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-slate-200 font-semibold">
                            <td class="py-2" colspan="3">Estimated total</td>
                            <td>₦{{ number_format($shoppingItems->sum(fn ($i) => $i->unit_price * $i->person_count)) }}</td>
                            @if ($isOwner)<td></td>@endif
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <p class="text-sm text-slate-500">No items on the shopping list yet.</p>
        @endif

        @if ($canSuggest)
            <form method="POST" action="{{ route('shopping.store', $pocket->id) }}" class="mt-4 grid sm:grid-cols-5 gap-2 items-end border-t border-slate-100 pt-4">
                @csrf
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium mb-1">Item @unless($isOwner)<span class="text-slate-400 font-normal">(suggestion)</span>@endunless</label>
                    <input name="name" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Rice (50kg)">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Unit price (₦)</label>
                    <input type="number" name="unit_price" value="0" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">People</label>
                    <input type="number" name="person_count" value="1" min="1" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                </div>
                <button class="rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-medium px-4 py-2">Add</button>
            </form>
        @endif
    </div>

    {{-- Charity drive (Sadaqah / fi-sabilillah) --}}
    @if ($charity)
        @php $c = $charity; @endphp
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
            <div class="flex items-center justify-between mb-1">
                <h3 class="font-semibold">🤲 {{ $c['project']->title }}</h3>
                @if ($isOwner)<a href="{{ route('charity.setup', $pocket->id) }}" class="text-sm text-brand-dark hover:underline">Edit drive →</a>@endif
            </div>
            @if ($c['project']->description)<p class="text-sm text-slate-500 mb-2">{{ $c['project']->description }}</p>@endif
            <p class="text-xs text-slate-400 mb-3">Sadaqah · individual donations are private (fi-sabilillah)</p>

            @if ($c['goal_type'] === 'amount' && $c['target_amount'] > 0)
                <x-progress-bar :percent="$c['percent']" label="Raised" :current="$c['raised']" :target="$c['target_amount']" />
            @elseif ($c['goal_type'] === 'items')
                <div class="space-y-3">
                    @foreach ($c['items'] as $it)
                        <div>
                            <div class="flex justify-between text-sm"><span>{{ $it['name'] }}</span><span class="text-slate-500">{{ $it['collected_quantity'] }} / {{ $it['target_quantity'] }} {{ $it['unit'] }}</span></div>
                            <div class="h-2 rounded-full bg-slate-100 mt-1 overflow-hidden"><div class="h-full bg-brand" style="width: {{ $it['percent'] }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-500">Total raised: ₦{{ number_format($c['raised']) }}</p>
            @endif

            <div class="grid grid-cols-2 gap-3 mt-4 text-center">
                <div class="rounded-lg bg-brand-light p-3"><div class="text-xs text-slate-500">Your total</div><div class="font-semibold text-brand-dark">₦{{ number_format($c['my_total']) }}</div></div>
                <div class="rounded-lg bg-slate-50 p-3"><div class="text-xs text-slate-500">Group total</div><div class="font-semibold">₦{{ number_format($c['group_total']) }}</div></div>
            </div>

            @if ($isMember)
                <form method="POST" action="{{ route('charity.donate', $pocket->id) }}" class="mt-4 border-t border-slate-100 pt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium mb-1">Donation amount (₦) — optional</label>
                        <input type="number" name="amount" min="0" value="0" class="w-full sm:w-56 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                    </div>
                    @if ($c['goal_type'] === 'items' && count($c['items']))
                        <div class="space-y-2">
                            <p class="text-xs font-medium text-slate-500">Or pledge items:</p>
                            @foreach ($c['items'] as $i => $it)
                                <div class="flex items-center gap-2 text-sm">
                                    <input type="hidden" name="items[{{ $i }}][goal_item_id]" value="{{ $it['id'] }}">
                                    <span class="w-40">{{ $it['name'] }} <span class="text-xs text-slate-400">{{ $it['unit'] }}</span></span>
                                    <input type="number" name="items[{{ $i }}][quantity]" min="0" value="0" class="w-24 rounded-lg border border-slate-300 px-2 py-1 focus:border-brand focus:ring-brand">
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Donate</button>
                </form>
            @endif

            @isset($c['breakdown'])
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <p class="text-xs font-medium text-slate-500 mb-2">Donor breakdown @unless($pocket->charity_donors_visible)<span class="text-slate-400">(admin only)</span>@endunless</p>
                    <ul class="divide-y divide-slate-100 text-sm">
                        @forelse ($c['breakdown'] as $b)
                            <li class="py-1.5 flex justify-between"><span>{{ $b['name'] }}</span><span class="text-slate-500">₦{{ number_format($b['total']) }}@if($b['items']) · {{ $b['items'] }} item(s)@endif</span></li>
                        @empty
                            <li class="py-1.5 text-slate-500">No donations yet.</li>
                        @endforelse
                    </ul>
                </div>
            @endisset
        </div>
    @elseif ($charityEnabled && $isOwner)
        <div class="bg-white rounded-[1.5rem] border-2 border-dashed border-slate-300 p-5 text-center">
            <h3 class="font-semibold mb-1">🤲 Add a charity drive</h3>
            <p class="text-sm text-slate-500 mb-3">Collect Sadaqah for orphans &amp; the needy alongside contributions. Individual donations stay private (fi-sabilillah).</p>
            <a href="{{ route('charity.setup', $pocket->id) }}" class="inline-block rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Set up charity</a>
        </div>
    @endif

    </div>{{-- /main column --}}

    {{-- Sidebar --}}
    <aside class="space-y-6">
        {{-- Members --}}
        @php $showHands = $isOwner || $pocket->members_visible; @endphp
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold">Members ({{ $members->where('status', 1)->count() }})</h3>
                @unless ($showHands)<span class="text-xs text-slate-400">hands are private</span>@endunless
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($members as $m)
                    @php $mine = $m->user_id == auth()->id(); @endphp
                    <li class="py-2 flex items-center justify-between text-sm">
                        <span>{{ $m->name }}@if($mine) <span class="text-xs text-brand-dark">(you)</span>@endif</span>
                        <span class="text-slate-400">
                            @if ($showHands || $mine){{ (int) $m->hand_count }} hand(s) · @endif{{ $m->status ? 'active' : 'pending' }}
                        </span>
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-500">No members yet.</li>
                @endforelse
            </ul>
        </div>

        {{-- Top contributors --}}
        <x-mini-leaderboard :rows="$contributors" title="Top contributors" />

        {{-- Payout account (member) --}}
        @if ($isMember)
            @php $chosenAcct = $myAccounts->firstWhere('id', (int) optional($mySlot)->bank_account_id); @endphp
            <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
                <h3 class="font-semibold mb-3">💳 My payout account</h3>
                @if ($myAccounts->isEmpty())
                    <a href="{{ route('settings') }}" class="btn-soft text-sm">Add a payout account</a>
                @else
                    <button type="button" onclick="document.getElementById('pocketAcctModal').classList.remove('hidden')" class="btn-soft text-sm">
                        {{ $chosenAcct ? $chosenAcct->bank.' · '.$chosenAcct->nuban : 'Set payout account' }}
                    </button>
                @endif
            </div>
        @endif
    </aside>
    </div>{{-- /hub grid --}}

    {{-- Disputes (full width) --}}
    @if ($canDispute)
        <div id="disputes" class="scroll-mt-24">
            <x-disputes type="pocket" :id="$pocket->id" :disputes="$disputes" :isAdmin="$isOwner" :canRaise="true" />
        </div>
    @endif

    {{-- Group chat (floating) --}}
    @if ($canChat)
        <x-group-chat type="pocket" :id="$pocket->id" :messages="$messages" :canPost="true" />
    @endif

    {{-- Payout account modal --}}
    @if ($isMember && $myAccounts->isNotEmpty())
            <div id="pocketAcctModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
                 onclick="if(event.target===this)this.classList.add('hidden')">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100">
                        <img src="{{ asset('images/keenpocket-icon.svg') }}" class="h-7 w-7 rounded-md" alt="KeenPocket">
                        <span class="font-semibold">Your payout account</span>
                        <button type="button" onclick="document.getElementById('pocketAcctModal').classList.add('hidden')" class="ml-auto text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('pockets.setAccount', $pocket->id) }}" class="p-5 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium mb-1">Where you'll receive cashback/payout</label>
                            <select name="bank_account_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                                <option value="">— choose account —</option>
                                @foreach ($myAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ (int) optional($mySlot)->bank_account_id === $acc->id ? 'selected' : '' }}>{{ $acc->account_name }} · {{ $acc->bank }} · {{ $acc->nuban }}</option>
                                @endforeach
                            </select>
                        </div>
                        <p class="text-xs text-slate-400">Manage your saved accounts in <a href="{{ route('settings') }}" class="text-brand-dark hover:underline">settings</a>.</p>
                        <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white font-medium py-2.5">Save account</button>
                    </form>
                </div>
            </div>
        @endif
@endif
@endsection
