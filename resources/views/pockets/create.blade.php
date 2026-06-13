@extends('layouts.app')
@section('title', 'New Pocket')
@section('heading', 'Create a Pocket')

@section('content')
    <div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
        <form method="POST" action="{{ route('pockets.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Title</label>
                <input name="title" value="{{ old('title') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="2026 Ramadan Pocket">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Year</label>
                <input type="number" name="year" value="{{ old('year', date('Y')) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">{{ old('description') }}</textarea>
            </div>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Start month</label>
                    <input type="number" name="start_month" value="{{ old('start_month', 1) }}" min="1" max="12" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Months</label>
                    <input type="number" name="month_count" value="{{ old('month_count', 12) }}" min="1" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Max members (0 = ∞)</label>
                    <input type="number" name="max_keens" value="{{ old('max_keens', 0) }}" min="0" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Amount per hand (₦)</label>
                    <input type="number" name="amount_per_hand" value="{{ old('amount_per_hand', 5000) }}" min="1" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Your hands</label>
                    <input type="number" name="hand_count" value="{{ old('hand_count', 1) }}" min="1" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Create pocket</button>
                <a href="{{ route('pockets.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-slate-600 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </div>
@endsection
