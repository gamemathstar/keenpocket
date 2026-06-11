@extends('layouts.app')
@section('title', 'New Contribution')
@section('heading', 'New Contribution')

@section('content')
    <div class="max-w-lg bg-white rounded-xl border border-slate-200 p-6">
        <p class="text-sm text-slate-500 mb-4">Raise a contribution invoice for <span class="font-medium text-slate-700">{{ $pocket->title }}</span>. The pocket owner can approve it@if(config('wallet.enabled')) , or pay it instantly from your wallet@endif.</p>
        <form method="POST" action="{{ route('invoices.store', $pocket->id) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Amount (₦)</label>
                <input type="number" name="amount" value="{{ old('amount', $pocket->amount_per_hand) }}" min="1" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Month</label>
                <select name="month" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                    @foreach (['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $m)
                        <option value="{{ $i + 1 }}">{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Create invoice</button>
                <a href="{{ route('pockets.show', $pocket->id) }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-slate-600 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </div>
@endsection
