@extends('layouts.app')
@section('title', 'New Contribution')
@section('heading', 'New Contribution')

@section('content')
    <a href="{{ route('pockets.show', $pocket->id) }}" class="inline-flex items-center text-sm text-brand-dark hover:underline mb-4">← Back to {{ $pocket->title }}</a>
    <div class="max-w-lg bg-white rounded-xl border border-slate-200 p-6">
        <p class="text-sm text-slate-500 mb-4">Contributing to <span class="font-medium text-slate-700">{{ $pocket->title }}</span> — ₦{{ number_format($monthly) }} per month. Enter how much you're paying; we'll split it across the months you owe.</p>
        <form method="POST" action="{{ route('invoices.preview', $pocket->id) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Total amount (₦)</label>
                <input type="number" name="amount" value="{{ old('amount', $monthly) }}" min="1" required autofocus
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
            </div>
            @if ($project)
                <div>
                    <label class="block text-sm font-medium mb-1">Of which, donation to “{{ $project->title }}” (₦)</label>
                    <input type="number" name="donation" value="{{ old('donation', 0) }}" min="0"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                    <p class="text-xs text-slate-400 mt-1">Optional Sadaqah — deducted from the total before splitting across months. Stays private.</p>
                </div>
            @endif
            <div class="flex gap-3 pt-2">
                <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Continue →</button>
                <a href="{{ route('pockets.show', $pocket->id) }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-slate-600 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </div>
@endsection
