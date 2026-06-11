@extends('layouts.app')
@section('title', 'New Adashi')
@section('heading', 'Create an Adashi')

@section('content')
    <div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
        <form method="POST" action="{{ route('adashi.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <input name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="Family Adashi">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Amount per cycle (₦)</label>
                    <input type="number" name="amount_per_cycle" value="{{ old('amount_per_cycle', 50000) }}" min="1" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Cycle length (days)</label>
                    <input type="number" name="cycle_duration_days" value="{{ old('cycle_duration_days', 30) }}" min="1" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Start date</label>
                    <input type="date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Rotation</label>
                    <select name="rotation_mode" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                        <option value="MANUAL">Manual</option>
                        <option value="AUTO">Auto</option>
                    </select>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="is_public" value="1" class="rounded border-slate-300 text-brand focus:ring-brand">
                List in the public directory (others can discover &amp; join)
            </label>
            <div class="flex gap-3 pt-2">
                <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Create adashi</button>
                <a href="{{ route('adashi.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-slate-600 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </div>
@endsection
