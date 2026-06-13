@extends('layouts.app')
@section('title', $pocket->title)
@section('heading', 'Pocket')

@section('content')
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
                @endif
            </div>
        </div>

        @if ($isMember && $target > 0)
            <div class="mt-5 border-t border-slate-100 pt-4">
                <x-progress-bar :percent="$progress" label="My contribution goal" :current="$contributed" :target="$target" />
            </div>
        @endif

        @if ($isMember && ($pocket->nuban || $pocket->bank))
            <div class="mt-4 border-t border-slate-100 pt-4 text-sm">
                <span class="text-slate-400">Pay contributions to:</span>
                <span class="font-medium">{{ $pocket->account_name ?: $pocket->title }}</span>
                @if ($pocket->bank) · {{ $pocket->bank }}@endif
                @if ($pocket->nuban) · <span class="font-mono">{{ $pocket->nuban }}</span>@endif
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
                    <div class="flex items-end gap-3">
                        <div>
                            <label class="block text-xs font-medium mb-1">Hands</label>
                            <input type="number" name="hand_count" value="1" min="1" class="w-24 rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                        </div>
                        @unless ($pocket->guarantor_required)
                            <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Request to join</button>
                        @endunless
                    </div>
                    @if ($pocket->guarantor_required)
                        <div>
                            <label class="block text-xs font-medium mb-1">Guarantor <span class="text-slate-400 font-normal">(phone or email of someone who'll vouch for you)</span></label>
                            <input name="guarantor_contact" class="w-full sm:w-80 rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="guarantor@example.com">
                        </div>
                        <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Request to join</button>
                    @endif
                </form>
            @endif
        @endif
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Members --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold mb-3">Members ({{ $members->where('status', 1)->count() }})</h3>
            <ul class="divide-y divide-slate-100">
                @forelse ($members as $m)
                    <li class="py-2 flex items-center justify-between text-sm">
                        <span>{{ $m->name }}</span>
                        <span class="text-slate-400">{{ (int) $m->hand_count }} hand(s) · {{ $m->status ? 'active' : 'pending' }}</span>
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

    <div class="mt-6">
        <x-mini-leaderboard :rows="$contributors" title="Top contributors" />
    </div>

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

    {{-- Rate the admin --}}
    @if (!$isOwner && $isMember)
        <div class="bg-white rounded-xl border border-slate-200 p-5 mt-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold">⭐ Rate the admin</h3>
                @if ($adminRating['count'])
                    <span class="text-sm text-slate-500">{{ number_format($adminRating['average'], 1) }} ★ · {{ $adminRating['count'] }} rating(s)</span>
                @endif
            </div>
            <form method="POST" action="{{ route('pockets.rateAdmin', $pocket->id) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium mb-1">Stars</label>
                    <select name="stars" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                        @for ($s = 5; $s >= 1; $s--)<option value="{{ $s }}" {{ (int) $myRating === $s ? 'selected' : '' }}>{{ $s }} ★</option>@endfor
                    </select>
                </div>
                <div class="flex-1 min-w-[12rem]">
                    <label class="block text-xs font-medium mb-1">Comment (optional)</label>
                    <input name="comment" maxlength="500" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="How is the admin running this pocket?">
                </div>
                <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">{{ $myRating ? 'Update rating' : 'Submit' }}</button>
            </form>
        </div>
    @elseif ($isOwner && $adminRating['count'])
        <div class="bg-white rounded-xl border border-slate-200 p-5 mt-6">
            <h3 class="font-semibold">⭐ Your admin rating</h3>
            <p class="text-sm text-slate-500 mt-1">{{ number_format($adminRating['average'], 1) }} ★ from {{ $adminRating['count'] }} member(s).</p>
        </div>
    @endif
@endsection
