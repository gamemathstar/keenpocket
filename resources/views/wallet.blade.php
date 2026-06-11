@extends('layouts.app')
@section('title', 'Wallet')
@section('heading', 'Wallet')

@section('content')
    @unless ($enabled)
        <div class="bg-white rounded-xl border border-slate-200 p-8 text-center max-w-lg">
            <div class="text-4xl mb-3">💳</div>
            <h3 class="font-semibold text-lg">Wallet is coming soon</h3>
            <p class="text-slate-500 text-sm mt-1">In-app wallet isn't enabled yet. Once on, you'll be able to fund a balance and pay contributions in one tap.</p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 gap-6 max-w-2xl mb-6">
            <div class="bg-gradient-to-r from-brand to-blue-500 text-white rounded-xl p-6">
                <div class="text-sm opacity-90">Available balance</div>
                <div class="text-4xl font-bold mt-1">₦{{ number_format($balance) }}</div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h3 class="font-semibold mb-1">Top up</h3>
                <p class="text-xs text-slate-500 mb-3">Add funds to pay contributions in one tap.</p>
                <form method="POST" action="{{ route('wallet.topup') }}" class="flex items-end gap-2">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-xs font-medium mb-1">Amount (₦)</label>
                        <input type="number" name="amount" value="5000" min="1" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                    </div>
                    <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-4 py-2">Add</button>
                </form>
            </div>
        </div>

        <h3 class="font-semibold mb-3">Recent activity</h3>
        <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100 max-w-2xl">
            @forelse ($transactions as $t)
                <div class="px-4 py-3 flex items-center justify-between text-sm">
                    <div>
                        <div class="font-medium capitalize">{{ $t->reason }}</div>
                        <div class="text-xs text-slate-400">{{ $t->created_at }}</div>
                    </div>
                    <div class="text-right">
                        <div class="{{ $t->type === 'credit' ? 'text-emerald-600' : 'text-slate-700' }} font-medium">{{ $t->type === 'credit' ? '+' : '−' }}₦{{ number_format($t->amount) }}</div>
                        <div class="text-xs text-slate-400">bal ₦{{ number_format($t->balance_after) }}</div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-6 text-sm text-slate-500 text-center">No transactions yet.</div>
            @endforelse
        </div>
    @endunless
@endsection
