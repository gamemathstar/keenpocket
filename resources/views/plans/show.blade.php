@extends('layouts.app')
@section('title', $plan->title)
@section('heading', 'Plan')

@section('content')
    <a href="{{ route('plans.index') }}" class="inline-flex items-center text-sm text-brand-dark hover:underline mb-4">← Back to Shopping</a>

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-dark">{{ $plan->title }}</h2>
            <p class="text-slate-500 text-sm mt-0.5">{{ $plan->periodLabel() }}@if(!$isOwner) · shared with you @endif · plan together, save together.</p>
        </div>
        @if ($isOwner)
            <form method="POST" action="{{ route('plans.archive', $plan->id) }}" onsubmit="return confirm('Archive this plan?')">
                @csrf
                <button class="text-sm text-slate-500 hover:text-slate-700">Archive</button>
            </form>
        @endif
    </div>

    {{-- Teamwork + estimated total --}}
    <div class="grid lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 rounded-[1.5rem] card-depth-brand bg-brand text-white p-6 flex items-center gap-4">
            <x-mascot :size="72" class="hidden sm:block drop-shadow-xl" />
            <div>
                <h3 class="text-lg font-extrabold">Teamwork makes the dream work!</h3>
                <p class="text-sm opacity-90 mt-1">Tick off what you buy and defer the rest — Mr. K keeps your list on budget. {{ $summary['purchased'] }} of {{ $summary['total'] }} items done so far.</p>
            </div>
        </div>
        <div class="rounded-[1.5rem] card-depth border-2 border-amber-200 bg-amber-50 p-6 text-center flex flex-col justify-center">
            <div class="text-[11px] font-bold uppercase tracking-wide text-amber-600">Estimated total</div>
            <div class="text-3xl font-extrabold text-amber-700 mt-1">₦{{ number_format($summary['estimated']) }}</div>
            @if ($summary['budget'] > 0)
                <div class="text-xs text-slate-500 mt-1">{{ $summary['percent_spent'] }}% of ₦{{ number_format($summary['budget']) }} budget</div>
                <div class="h-2 rounded-full bg-amber-100 overflow-hidden mt-2"><div class="h-full {{ $summary['over_budget'] ? 'bg-amber-500' : 'bg-brand' }} rounded-full" style="width: {{ $summary['percent_spent'] }}%"></div></div>
            @endif
        </div>
    </div>

    {{-- Shopping list + add/share sidebar --}}
    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Shopping list --}}
        <div class="lg:col-span-2 bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <div>
                    <h3 class="font-extrabold">🛒 Shopping list</h3>
                    <div class="text-xs font-bold text-slate-400">{{ $summary['purchased'] }} bought · {{ $summary['pending'] }} pending @if($summary['deferred']) · {{ $summary['deferred'] }} deferred @endif</div>
                </div>
                <div class="flex gap-2">
                    <select id="planFilter" class="rounded-full border-2 border-slate-200 bg-slate-50 text-sm font-bold text-slate-600 px-3 py-1.5 focus:border-brand focus:ring-brand">
                        <option value="all">Filter: All</option>
                        <option value="pending">Pending</option>
                        <option value="purchased">Bought</option>
                        <option value="deferred">Deferred</option>
                    </select>
                    <select id="planSort" class="rounded-full border-2 border-slate-200 bg-slate-50 text-sm font-bold text-slate-600 px-3 py-1.5 focus:border-brand focus:ring-brand">
                        <option value="default">Sort: Added</option>
                        <option value="name">Name A–Z</option>
                        <option value="price">Price high→low</option>
                        <option value="status">Status</option>
                    </select>
                </div>
            </div>
            <ul id="planItems" class="space-y-3">
                @forelse ($items as $item)
                    <li class="plan-item border-2 border-slate-100 rounded-2xl p-4" data-status="{{ $item->status }}" data-name="{{ strtolower($item->name) }}" data-price="{{ (int) $item->lineValue() }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    @if ($item->priority)<span title="Deferred last time — prioritise">⭐</span>@endif
                                    <span class="font-bold {{ $item->status === 'purchased' ? 'line-through text-slate-400' : '' }}">{{ $item->name }}</span>
                                    @php $badge = $item->status === 'purchased' ? 'bg-emerald-100 text-emerald-700' : ($item->status === 'deferred' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'); @endphp
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $badge }}">{{ $item->status }}</span>
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ $item->quantity }}{{ $item->unit ? ' '.$item->unit : '' }}
                                    @if ($item->unit_price) · ₦{{ number_format($item->unit_price) }} each · <span class="font-bold text-brand-dark">₦{{ number_format($item->lineValue()) }}</span>@endif
                                    @if ($item->claimed_by) · 🛒 {{ optional($item->claimer)->name ?? 'claimed' }}@endif
                                    @if ($item->note) · {{ $item->note }}@endif
                                </div>
                            </div>
                            {{-- Edit + remove icons (top-right) --}}
                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button" onclick="document.getElementById('edit-{{ $item->id }}').classList.toggle('hidden')" title="Edit item"
                                        class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-brand-dark text-base">✏️</button>
                                <form method="POST" action="{{ route('plans.items.destroy', $item->id) }}" onsubmit="return confirm('Remove this item?')">
                                    @csrf
                                    <button title="Remove item" class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500 text-xl leading-none">&times;</button>
                                </form>
                            </div>
                        </div>

                        {{-- Inline edit form (toggled by the pencil) --}}
                        <form id="edit-{{ $item->id }}" method="POST" action="{{ route('plans.items.update', $item->id) }}" class="hidden mt-3 grid grid-cols-2 gap-2 p-3 bg-slate-50 rounded-xl">
                            @csrf
                            <input type="hidden" name="action" value="edit">
                            <input name="name" value="{{ $item->name }}" class="col-span-2 rounded border border-slate-300 px-2 py-1 text-sm" placeholder="Name">
                            <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" class="rounded border border-slate-300 px-2 py-1 text-sm" placeholder="Qty">
                            <input name="unit" value="{{ $item->unit }}" class="rounded border border-slate-300 px-2 py-1 text-sm" placeholder="Unit">
                            <input type="number" name="unit_price" value="{{ $item->unit_price }}" min="0" class="rounded border border-slate-300 px-2 py-1 text-sm" placeholder="₦/unit">
                            <input name="note" value="{{ $item->note }}" class="rounded border border-slate-300 px-2 py-1 text-sm" placeholder="Note">
                            <button class="col-span-2 text-xs rounded-md bg-brand hover:bg-brand-dark text-white px-3 py-1.5">Save changes</button>
                        </form>

                        {{-- Status actions --}}
                        <div class="flex flex-wrap gap-1.5 mt-3">
                            @if ($item->status !== 'purchased')
                                <form method="POST" action="{{ route('plans.items.update', $item->id) }}">@csrf<input type="hidden" name="action" value="purchased"><button class="text-xs rounded-md border border-emerald-300 text-emerald-700 hover:bg-emerald-50 px-2.5 py-1">✓ Bought</button></form>
                            @endif
                            @if ($item->status !== 'deferred')
                                <form method="POST" action="{{ route('plans.items.update', $item->id) }}">@csrf<input type="hidden" name="action" value="deferred"><button class="text-xs rounded-md border border-amber-300 text-amber-700 hover:bg-amber-50 px-2.5 py-1">Defer</button></form>
                            @endif
                            @if ($item->status !== 'pending')
                                <form method="POST" action="{{ route('plans.items.update', $item->id) }}">@csrf<input type="hidden" name="action" value="pending"><button class="text-xs rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 px-2.5 py-1">Reset</button></form>
                            @endif
                            <form method="POST" action="{{ route('plans.items.update', $item->id) }}">@csrf<input type="hidden" name="action" value="claim"><button class="text-xs rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 px-2.5 py-1">{{ $item->claimed_by == auth()->id() ? 'Unclaim' : 'I’ll buy it' }}</button></form>
                        </div>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">No items yet — add your first in the panel →</li>
                @endforelse
            </ul>
            <div id="planNoMatch" class="text-center text-sm text-slate-500 py-4" style="display:none">No items match this filter.</div>
            <div class="mt-3 rounded-2xl border-2 border-dashed border-slate-300 text-center p-6 flex flex-col items-center justify-center gap-1.5 text-slate-400">
                <span class="text-3xl">🧺</span>
                <span class="font-bold text-slate-500">Add more items for a complete plan</span>
                <span class="text-xs">Bigger lists, better budgeting.</span>
            </div>
        </div>

        {{-- Sidebar: add item + shared with --}}
        <div class="space-y-6">
            {{-- Add new item --}}
            <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
                <h3 class="font-extrabold mb-3">➕ Add new item</h3>
                <form method="POST" action="{{ route('plans.items.store', $plan->id) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium mb-1">Item name</label>
                        <input name="name" required class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="e.g. Rice (50kg)">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium mb-1">Unit price (₦)</label>
                            <input type="number" name="unit_price" min="0" class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="opt.">
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Quantity</label>
                            <input type="number" name="quantity" value="1" min="1" class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Unit <span class="text-slate-400 font-normal">(optional)</span></label>
                        <input name="unit" class="w-full rounded-xl border-2 border-slate-200 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="bag, kg, pack…">
                    </div>
                    <button class="w-full rounded-xl bg-brand hover:bg-brand-dark text-white font-bold py-2.5">Add to list</button>
                    <p class="text-xs text-slate-400">Price is optional — leave it blank to plan by quantity only.</p>
                </form>
            </div>

            {{-- Shared with --}}
            <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
                <h3 class="font-extrabold mb-3">👫 Shared with</h3>
                <ul class="divide-y divide-slate-100 text-sm mb-3">
                    <li class="py-2 flex items-center gap-2">
                        <x-avatar :user="optional($plan->owner)->name ?? 'U'" :size="28" />
                        <span class="font-medium">{{ optional($plan->owner)->name }}</span>
                        <span class="text-xs text-slate-400">owner</span>
                    </li>
                    @forelse ($collaborators as $col)
                        <li class="py-2 flex items-center gap-2">
                            <x-avatar :user="$col->name" :size="28" />
                            <span class="flex-1 truncate">{{ $col->name }}</span>
                            @if ($isOwner)
                                <form method="POST" action="{{ route('plans.unshare', [$plan->id, $col->id]) }}">@csrf<button class="text-xs text-red-500 hover:underline">remove</button></form>
                            @endif
                        </li>
                    @empty
                        <li class="py-2 text-slate-500">Not shared yet.</li>
                    @endforelse
                </ul>
                @if ($isOwner)
                    <div class="border-t border-slate-100 pt-3">
                        <label class="block text-xs font-medium mb-2">Share with a friend</label>
                        @if ($friends->count())
                            <div class="flex flex-wrap gap-2">
                                @foreach ($friends as $friend)
                                    <form method="POST" action="{{ route('plans.share', $plan->id) }}">
                                        @csrf
                                        <input type="hidden" name="friend_id" value="{{ $friend->id }}">
                                        <button class="inline-flex items-center gap-1.5 rounded-full border-2 border-slate-200 hover:border-brand hover:bg-brand-light text-sm font-bold text-slate-700 pl-1 pr-3 py-1">
                                            <x-avatar :user="$friend->name" :size="24" />
                                            <span class="text-brand-dark leading-none">+</span> {{ $friend->name }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-slate-500">No friends to add yet. <a href="{{ route('friends.index') }}" class="text-brand-dark font-bold hover:underline">Add friends →</a></p>
                        @endif
                        <p class="text-xs text-slate-400 mt-2">You can only share with people on your friends list.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function () {
            var ul = document.getElementById('planItems');
            var fil = document.getElementById('planFilter');
            var sort = document.getElementById('planSort');
            if (!ul || !fil || !sort) return;
            var order = Array.prototype.slice.call(ul.querySelectorAll('.plan-item'));
            function apply() {
                var f = fil.value, s = sort.value, shown = 0;
                order.forEach(function (li) {
                    var ok = f === 'all' || li.dataset.status === f;
                    li.style.display = ok ? '' : 'none';
                    if (ok) shown++;
                });
                var arr = order.slice();
                if (s === 'name') arr.sort(function (a, b) { return a.dataset.name.localeCompare(b.dataset.name); });
                else if (s === 'price') arr.sort(function (a, b) { return (+b.dataset.price) - (+a.dataset.price); });
                else if (s === 'status') arr.sort(function (a, b) { return a.dataset.status.localeCompare(b.dataset.status); });
                arr.forEach(function (li) { ul.appendChild(li); });
                var none = document.getElementById('planNoMatch');
                if (none) none.style.display = shown ? 'none' : '';
            }
            fil.addEventListener('change', apply);
            sort.addEventListener('change', apply);
        })();
    </script>
@endsection
