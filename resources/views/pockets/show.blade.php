@extends('layouts.app')
@section('title', $pocket->title)
@section('heading', 'Pocket')

@section('content')
@if (!$isMember && !$isOwner)
    @include('pockets._public')
@else
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold">{{ $pocket->title }}</h2>
                <p class="text-slate-500 text-sm mt-1">{{ $pocket->description ?: 'No description.' }}</p>
                <p class="text-xs text-slate-400 mt-2">Organised by
                    @if ($owner)<a href="{{ route('users.show', $owner->id) }}" class="text-brand-dark hover:underline">{{ $owner->name }}</a>@else — @endif
                    · {{ $pocket->month_count }} months · {{ $pocket->year }}</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-semibold">₦{{ number_format($pocket->amount_per_hand) }}</div>
                <div class="text-xs text-slate-400">per hand</div>
                @if ($isOwner)
                    <a href="{{ route('pockets.manage', $pocket->id) }}" class="inline-block mt-2 text-sm text-brand-dark hover:underline">Manage →</a>
                    @if ($adminRating['count'])
                        <div class="mt-2 text-xs text-slate-500">⭐ {{ number_format($adminRating['average'], 1) }} ({{ $adminRating['count'] }})</div>
                    @endif
                @elseif ($isMember)
                    <div class="mt-2">
                        <x-rate-admin :action="route('pockets.rateAdmin', $pocket->id)" :average="$adminRating" :my="$myRating" />
                    </div>
                @endif
                <div class="mt-2">
                    <x-share-card cardTitle="Join my pocket" :cardBig="$pocket->title"
                        :cardSub="'₦'.number_format($pocket->amount_per_hand).'/hand · '.$pocket->month_count.' months'"
                        :shareText="'Join my KeenPocket savings pocket: '.$pocket->title.' (₦'.number_format($pocket->amount_per_hand).'/hand).'"
                        :shareUrl="route('pockets.show', $pocket->id)" />
                </div>
            </div>
        </div>

        @if ($isMember && $target > 0)
            <div class="mt-5 border-t border-slate-100 pt-4">
                <x-progress-bar :percent="$progress" label="My contribution goal" :current="$contributed" :target="$target" />
            </div>
        @endif

        @if ($charity)
            <div class="mt-4 border-t border-slate-100 pt-4">
                @if ($charity['goal_type'] === 'amount' && $charity['target_amount'] > 0)
                    <x-progress-bar :percent="$charity['percent']" label="🤲 Donations" :current="$charity['raised']" :target="$charity['target_amount']" />
                @else
                    <div class="flex justify-between text-sm"><span class="text-slate-500">🤲 Donations raised</span><span class="font-medium">₦{{ number_format($charity['raised']) }}</span></div>
                    <div class="h-2.5 rounded-full bg-slate-100 mt-1 overflow-hidden"><div class="h-full bg-amber-400" style="width: {{ $charity['raised'] > 0 ? 100 : 0 }}%"></div></div>
                @endif
                <p class="text-xs text-slate-500 mt-1">You've donated <span class="font-semibold text-brand-dark">₦{{ number_format($charity['my_total']) }}</span> · Group total ₦{{ number_format($charity['group_total']) }}</p>
            </div>
        @endif

        @if ($isMember && ($pocket->nuban || $pocket->bank))
            <div class="mt-4 border-t border-slate-100 pt-4 flex items-center flex-wrap gap-1.5 text-sm">
                <span class="text-slate-400">Pay contributions to:</span>
                <span class="font-medium">{{ $pocket->account_name ?: $pocket->title }}</span>
                @if ($pocket->bank)<span class="text-slate-400">· {{ $pocket->bank }}</span>@endif
                @if ($pocket->nuban)
                    <span class="text-slate-400">·</span><span class="font-mono">{{ $pocket->nuban }}</span>
                    <button type="button" onclick="kpCopy('{{ $pocket->nuban }}', this)" title="Copy account number" class="text-base text-slate-400 hover:text-brand-dark leading-none">📋</button>
                @endif
            </div>
        @endif

        @if (!$isMember && !$isOwner && $hasPending)
            <div class="mt-5 border-t border-slate-100 pt-4 text-sm text-amber-700 bg-amber-50 rounded-lg px-3 py-2">
                ⏳ Your join request is pending @if($pocket->guarantor_required) — your guarantor must recommend you, then the admin will accept.@else the admin's approval.@endif
            </div>
        @elseif (!$isMember && !$isOwner)
            @if (!$pocket->status)
                <div class="mt-5 border-t border-slate-100 pt-4 text-sm text-slate-500">🔒 This pocket is invitation-only. Ask the admin to invite you.</div>
            @else
                <form method="POST" action="{{ route('pockets.join', $pocket->id) }}" class="mt-5 border-t border-slate-100 pt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium mb-1">Hands</label>
                        <input type="number" name="hand_count" value="1" min="1" class="w-24 rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                    </div>
                    @if ($pocket->guarantor_required)
                        <div>
                            <label class="block text-xs font-medium mb-1">Guarantor <span class="text-slate-400 font-normal">(phone or email of someone who'll vouch for you)</span></label>
                            <input name="guarantor_contact" class="w-full sm:w-80 rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="guarantor@example.com">
                        </div>
                    @endif
                    @if ($pocket->rules)
                        <div class="rounded-lg bg-slate-50 border border-slate-200 text-sm text-slate-600 px-3 py-2.5 whitespace-pre-line"><span class="font-semibold">Group rules:</span> {{ $pocket->rules }}</div>
                    @endif
                    <x-terms-notice variant="join" />
                    <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Request to join</button>
                </form>
            @endif
        @endif
    </div>

    {{-- Group rules + owner tools --}}
    @if ($pocket->rules || $isOwner)
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
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

    {{-- Owner: members' payments awaiting approval --}}
    @if ($isOwner)
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
            <h3 class="font-semibold mb-3">Payments to approve
                @if ($pendingApprovals->isNotEmpty())<span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ $pendingApprovals->count() }}</span>@endif
            </h3>
            @forelse ($pendingApprovals as $inv)
                <div class="py-2 flex items-center justify-between text-sm border-b border-slate-100 last:border-0">
                    <span><span class="font-medium">{{ $inv->name }}</span> <span class="text-xs text-slate-400">· {{ $inv->invoice_no }}</span></span>
                    <span class="flex items-center gap-3">
                        <span class="font-medium">₦{{ number_format($inv->amount) }}</span>
                        <form method="POST" action="{{ route('invoices.markPaid', $inv->id) }}">
                            @csrf
                            <button class="text-xs rounded-md bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1">Mark paid</button>
                        </form>
                        <form method="POST" action="{{ route('invoices.decline', $inv->id) }}" onsubmit="return confirm('Decline this payment request?')">
                            @csrf
                            <button class="text-xs rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 px-3 py-1">Decline</button>
                        </form>
                    </span>
                </div>
            @empty
                <p class="text-sm text-slate-500">No payments awaiting approval. 🎉</p>
            @endforelse
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Members --}}
        @php $showHands = $isOwner || $pocket->members_visible; @endphp
        <div class="bg-white rounded-xl border border-slate-200 p-5">
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

        {{-- My invoices --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
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
    </div>

    @if ($isMember)
        @php $chosenAcct = $myAccounts->firstWhere('id', (int) optional($mySlot)->bank_account_id); @endphp
        <div class="mt-6">
            @if ($myAccounts->isEmpty())
                <a href="{{ route('settings') }}" class="btn-soft text-sm">💳 Add a payout account</a>
            @else
                <button type="button" onclick="document.getElementById('pocketAcctModal').classList.remove('hidden')" class="btn-soft text-sm">
                    💳 {{ $chosenAcct ? $chosenAcct->bank.' · '.$chosenAcct->nuban : 'Set payout account' }}
                </button>
            @endif
        </div>

        @if ($myAccounts->isNotEmpty())
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

    <div class="mt-6">
        <x-mini-leaderboard :rows="$contributors" title="Top contributors" />
    </div>

    @if ($canChat)
        <x-group-chat type="pocket" :id="$pocket->id" :messages="$messages" :canPost="true" />
    @endif

    @if ($canDispute)
        <x-disputes type="pocket" :id="$pocket->id" :disputes="$disputes" :isAdmin="$isOwner" :canRaise="true" />
    @endif

    {{-- Shopping list (group buying) --}}
    @php $canSuggest = $isOwner || ($pocket->open_purchasing_item && $isMember); @endphp
    <div class="bg-white rounded-xl border border-slate-200 p-5 mt-6">
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
        <div class="bg-white rounded-xl border border-slate-200 p-5 mt-6">
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
        <div class="bg-white rounded-xl border border-dashed border-slate-300 p-5 mt-6 text-center">
            <h3 class="font-semibold mb-1">🤲 Add a charity drive</h3>
            <p class="text-sm text-slate-500 mb-3">Collect Sadaqah for orphans &amp; the needy alongside contributions. Individual donations stay private (fi-sabilillah).</p>
            <a href="{{ route('charity.setup', $pocket->id) }}" class="inline-block rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Set up charity</a>
        </div>
    @endif
@endif
@endsection
