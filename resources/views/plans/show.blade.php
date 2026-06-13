@extends('layouts.app')
@section('title', $plan->title)
@section('heading', 'Plan')

@section('content')
    {{-- Header + summary --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold">{{ $plan->title }}</h2>
                <p class="text-xs text-slate-400 mt-1">{{ $plan->month ?: 'No month set' }}@if(!$isOwner) · shared with you @endif</p>
            </div>
            @if ($isOwner)
                <form method="POST" action="{{ route('plans.archive', $plan->id) }}" onsubmit="return confirm('Archive this plan?')">
                    @csrf
                    <button class="text-sm text-slate-500 hover:text-slate-700">Archive</button>
                </form>
            @endif
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5">
            <div class="rounded-lg bg-slate-50 p-3 text-center"><div class="text-xs text-slate-500">Items</div><div class="font-semibold">{{ $summary['total'] }}</div></div>
            <div class="rounded-lg bg-emerald-50 p-3 text-center"><div class="text-xs text-slate-500">Bought</div><div class="font-semibold text-emerald-700">{{ $summary['purchased'] }}</div></div>
            <div class="rounded-lg bg-amber-50 p-3 text-center"><div class="text-xs text-slate-500">Deferred</div><div class="font-semibold text-amber-700">{{ $summary['deferred'] }}</div></div>
            <div class="rounded-lg bg-brand-light p-3 text-center"><div class="text-xs text-slate-500">Est. total</div><div class="font-semibold text-brand-dark">₦{{ number_format($summary['estimated']) }}</div></div>
        </div>

        @if ($summary['budget'] > 0)
            <div class="mt-4">
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-slate-500">Spent ₦{{ number_format($summary['spent']) }} of ₦{{ number_format($summary['budget']) }}</span>
                    <span class="{{ $summary['over_budget'] ? 'text-amber-600' : 'text-slate-500' }}">
                        @if ($summary['over_budget']) over budget by ₦{{ number_format($summary['estimated'] - $summary['budget']) }} @else ₦{{ number_format($summary['remaining']) }} left @endif
                    </span>
                </div>
                <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden"><div class="h-full {{ $summary['over_budget'] ? 'bg-amber-500' : 'bg-brand' }}" style="width: {{ $summary['percent_spent'] }}%"></div></div>
            </div>
        @endif
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Items --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold mb-3">Items</h3>
            <ul class="divide-y divide-slate-100">
                @forelse ($items as $item)
                    <li class="py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    @if ($item->priority)<span title="Deferred last time — prioritise">⭐</span>@endif
                                    <span class="font-medium {{ $item->status === 'purchased' ? 'line-through text-slate-400' : '' }}">{{ $item->name }}</span>
                                    @php $badge = $item->status === 'purchased' ? 'bg-emerald-100 text-emerald-700' : ($item->status === 'deferred' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'); @endphp
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $badge }}">{{ $item->status }}</span>
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ $item->quantity }}{{ $item->unit ? ' '.$item->unit : '' }}
                                    @if ($item->unit_price) · ₦{{ number_format($item->unit_price) }} each · <span class="font-medium">₦{{ number_format($item->lineValue()) }}</span>@endif
                                    @if ($item->claimed_by) · 🛒 {{ optional($item->claimer)->name ?? 'claimed' }}@endif
                                    @if ($item->note) · {{ $item->note }}@endif
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            @if ($item->status !== 'purchased')
                                <form method="POST" action="{{ route('plans.items.update', $item->id) }}">@csrf<input type="hidden" name="action" value="purchased"><button class="text-xs rounded-md bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1">✓ Bought</button></form>
                            @endif
                            @if ($item->status !== 'deferred')
                                <form method="POST" action="{{ route('plans.items.update', $item->id) }}">@csrf<input type="hidden" name="action" value="deferred"><button class="text-xs rounded-md border border-amber-300 text-amber-700 hover:bg-amber-50 px-2.5 py-1">Defer</button></form>
                            @endif
                            @if ($item->status !== 'pending')
                                <form method="POST" action="{{ route('plans.items.update', $item->id) }}">@csrf<input type="hidden" name="action" value="pending"><button class="text-xs rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 px-2.5 py-1">Reset</button></form>
                            @endif
                            <form method="POST" action="{{ route('plans.items.update', $item->id) }}">@csrf<input type="hidden" name="action" value="claim"><button class="text-xs rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 px-2.5 py-1">{{ $item->claimed_by == auth()->id() ? 'Unclaim' : 'I’ll buy it' }}</button></form>
                            <details class="inline-block">
                                <summary class="text-xs rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 px-2.5 py-1 cursor-pointer list-none">Edit</summary>
                                <form method="POST" action="{{ route('plans.items.update', $item->id) }}" class="mt-2 grid grid-cols-2 gap-2 p-3 bg-slate-50 rounded-lg">
                                    @csrf
                                    <input type="hidden" name="action" value="edit">
                                    <input name="name" value="{{ $item->name }}" class="col-span-2 rounded border border-slate-300 px-2 py-1 text-sm" placeholder="Name">
                                    <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" class="rounded border border-slate-300 px-2 py-1 text-sm" placeholder="Qty">
                                    <input name="unit" value="{{ $item->unit }}" class="rounded border border-slate-300 px-2 py-1 text-sm" placeholder="Unit">
                                    <input type="number" name="unit_price" value="{{ $item->unit_price }}" min="0" class="rounded border border-slate-300 px-2 py-1 text-sm" placeholder="₦/unit">
                                    <input name="note" value="{{ $item->note }}" class="rounded border border-slate-300 px-2 py-1 text-sm" placeholder="Note">
                                    <button class="col-span-2 text-xs rounded-md bg-brand hover:bg-brand-dark text-white px-3 py-1.5">Save</button>
                                </form>
                            </details>
                            <form method="POST" action="{{ route('plans.items.destroy', $item->id) }}" onsubmit="return confirm('Remove this item?')">@csrf<button class="text-xs text-red-500 hover:underline px-2 py-1">remove</button></form>
                        </div>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">No items yet — add your first below.</li>
                @endforelse
            </ul>

            {{-- Add item --}}
            <form method="POST" action="{{ route('plans.items.store', $plan->id) }}" class="mt-4 grid grid-cols-12 gap-2 items-end border-t border-slate-100 pt-4">
                @csrf
                <div class="col-span-12 sm:col-span-5">
                    <label class="block text-xs font-medium mb-1">Item</label>
                    <input name="name" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="Rice (50kg)">
                </div>
                <div class="col-span-4 sm:col-span-2">
                    <label class="block text-xs font-medium mb-1">Qty</label>
                    <input type="number" name="quantity" value="1" min="1" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                </div>
                <div class="col-span-4 sm:col-span-2">
                    <label class="block text-xs font-medium mb-1">Unit</label>
                    <input name="unit" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="bag">
                </div>
                <div class="col-span-4 sm:col-span-2">
                    <label class="block text-xs font-medium mb-1">₦/unit</label>
                    <input type="number" name="unit_price" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="opt.">
                </div>
                <div class="col-span-12 sm:col-span-1">
                    <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-medium px-3 py-2">Add</button>
                </div>
            </form>
            <p class="text-xs text-slate-400 mt-2">Price is optional — leave it blank to plan by quantity only.</p>
        </div>

        {{-- Sharing --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 h-fit">
            <h3 class="font-semibold mb-3">👫 Shared with</h3>
            <ul class="divide-y divide-slate-100 text-sm mb-3">
                <li class="py-2 flex justify-between"><span>{{ optional($plan->owner)->name }} <span class="text-xs text-slate-400">(owner)</span></span></li>
                @forelse ($collaborators as $col)
                    <li class="py-2 flex justify-between items-center">
                        <span>{{ $col->name }}</span>
                        @if ($isOwner)
                            <form method="POST" action="{{ route('plans.unshare', [$plan->id, $col->id]) }}">@csrf<button class="text-xs text-red-500 hover:underline">remove</button></form>
                        @endif
                    </li>
                @empty
                    <li class="py-2 text-slate-500">Not shared yet.</li>
                @endforelse
            </ul>
            @if ($isOwner)
                <form method="POST" action="{{ route('plans.share', $plan->id) }}" class="border-t border-slate-100 pt-3">
                    @csrf
                    <label class="block text-xs font-medium mb-1">Share with (phone or email)</label>
                    <div class="flex gap-2">
                        <input name="contact" required class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand" placeholder="spouse@example.com">
                        <button class="rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-medium px-3 py-2">Share</button>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">They must have a KeenPocket account.</p>
                </form>
            @endif
        </div>
    </div>
@endsection
