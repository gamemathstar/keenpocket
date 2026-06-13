@extends('layouts.app')
@section('title', 'New plan')
@section('heading', 'New plan')

@section('content')
    <div class="max-w-lg bg-white rounded-xl border border-slate-200 p-6">
        <form method="POST" action="{{ route('plans.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Plan title</label>
                <input name="title" value="{{ old('title') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="June groceries">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Month</label>
                    <input type="month" name="month" value="{{ old('month') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Budget (₦) — optional</label>
                    <input type="number" name="budget" value="{{ old('budget') }}" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="e.g. when salary lands">
                </div>
            </div>

            @if ($carrySource)
                @php $deferredCount = $carrySource->items()->where('status', 'deferred')->count(); @endphp
                <label class="flex items-start gap-2 rounded-lg bg-amber-50 border border-amber-200 p-3">
                    <input type="checkbox" name="carry_from" value="{{ $carrySource->id }}" checked class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand">
                    <span class="text-sm text-amber-800">Carry over <strong>{{ $deferredCount }}</strong> deferred item(s) from “{{ $carrySource->title }}” — they’ll be marked ⭐ priority this time.</span>
                </label>
            @endif

            <div class="flex gap-3 pt-2">
                <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Create plan</button>
                <a href="{{ route('plans.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-slate-600 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </div>
@endsection
