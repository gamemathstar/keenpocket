@extends('layouts.app')
@section('title', 'Charity drive')
@section('heading', 'Charity drive')

@section('content')
    @php
        $goalType = old('goal_type', $project->goal_type ?? 'amount');
        $rows = $goalItems->values();
        $blankNeeded = max(0, 5 - $rows->count());
    @endphp
    <a href="{{ route('pockets.show', $pocket->id) }}" class="inline-flex items-center text-sm text-brand-dark hover:underline mb-4">← Back to {{ $pocket->title }}</a>
    <div class="max-w-2xl bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6">
        <div class="mb-4">
            <h2 class="text-xl font-semibold">🤲 {{ $project ? 'Edit' : 'Set up' }} charity drive</h2>
            <p class="text-sm text-slate-500 mt-1">Collect Sadaqah for orphans &amp; the needy. Individual donations stay private (fi-sabilillah) — members only see their own total and the group total.</p>
        </div>

        <form method="POST" action="{{ route('charity.store', $pocket->id) }}" class="space-y-4">
            @csrf

            <label class="flex items-center gap-2">
                <input type="checkbox" name="enabled" value="1" {{ ($pocket->charity_enabled ?? false) ? 'checked' : '' }} class="rounded border-slate-300 text-brand focus:ring-brand">
                <span class="text-sm font-medium">Enable charity collection for this pocket</span>
            </label>

            <div>
                <label class="block text-sm font-medium mb-1">Drive title</label>
                <input name="title" value="{{ old('title', $project->title ?? '') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="Ramadan food for the needy">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Description (optional)</label>
                <textarea name="description" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">{{ old('description', $project->description ?? '') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Goal type</label>
                <select name="goal_type" id="goalType" onchange="kpToggleGoal()" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                    <option value="amount" {{ $goalType === 'amount' ? 'selected' : '' }}>Money target (₦)</option>
                    <option value="items" {{ $goalType === 'items' ? 'selected' : '' }}>Items (rice, clothing, meat…)</option>
                </select>
            </div>

            <div id="amountGoal" class="{{ $goalType === 'items' ? 'hidden' : '' }}">
                <label class="block text-sm font-medium mb-1">Target amount (₦) — 0 for no fixed target</label>
                <input type="number" name="target_amount" min="0" value="{{ old('target_amount', $project->target_amount ?? 0) }}" class="w-full sm:w-64 rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
            </div>

            <div id="itemsGoal" class="{{ $goalType === 'items' ? '' : 'hidden' }}">
                <label class="block text-sm font-medium mb-1">Item goals</label>
                <div class="space-y-2">
                    <div class="hidden sm:grid grid-cols-12 gap-2 text-xs text-slate-400">
                        <span class="col-span-5">Item</span><span class="col-span-2">Unit</span><span class="col-span-2">Target qty</span><span class="col-span-3">₦/unit (optional)</span>
                    </div>
                    @foreach ($rows as $i => $gi)
                        <div class="grid grid-cols-12 gap-2">
                            <input name="items[{{ $i }}][name]" value="{{ $gi->name }}" class="col-span-5 rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand focus:ring-brand" placeholder="Bag of rice">
                            <input name="items[{{ $i }}][unit]" value="{{ $gi->unit }}" class="col-span-2 rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand focus:ring-brand" placeholder="bag">
                            <input type="number" name="items[{{ $i }}][target_quantity]" value="{{ $gi->target_quantity }}" min="0" class="col-span-2 rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand focus:ring-brand">
                            <input type="number" name="items[{{ $i }}][unit_price]" value="{{ $gi->unit_price }}" min="0" class="col-span-3 rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand focus:ring-brand">
                        </div>
                    @endforeach
                    @for ($b = 0; $b < $blankNeeded; $b++)
                        @php $idx = $rows->count() + $b; @endphp
                        <div class="grid grid-cols-12 gap-2">
                            <input name="items[{{ $idx }}][name]" class="col-span-5 rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand focus:ring-brand" placeholder="Item name">
                            <input name="items[{{ $idx }}][unit]" class="col-span-2 rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand focus:ring-brand" placeholder="unit">
                            <input type="number" name="items[{{ $idx }}][target_quantity]" min="0" class="col-span-2 rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand focus:ring-brand">
                            <input type="number" name="items[{{ $idx }}][unit_price]" min="0" class="col-span-3 rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand focus:ring-brand">
                        </div>
                    @endfor
                </div>
            </div>

            <label class="flex items-center gap-2 border-t border-slate-100 pt-4">
                <input type="checkbox" name="donors_visible" value="1" {{ ($pocket->charity_donors_visible ?? false) ? 'checked' : '' }} class="rounded border-slate-300 text-brand focus:ring-brand">
                <span class="text-sm">Publish a donor honour-roll to all members <span class="text-slate-400">(off = fully private)</span></span>
            </label>

            <div class="flex gap-3 pt-2">
                <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Save drive</button>
                <a href="{{ route('pockets.show', $pocket->id) }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-slate-600 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        function kpToggleGoal() {
            var t = document.getElementById('goalType').value;
            document.getElementById('amountGoal').classList.toggle('hidden', t === 'items');
            document.getElementById('itemsGoal').classList.toggle('hidden', t !== 'items');
        }
    </script>
@endsection
