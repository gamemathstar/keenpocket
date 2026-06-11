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
        <div class="bg-gradient-to-r from-brand to-emerald-600 text-white rounded-xl p-6 mb-6 max-w-lg">
            <div class="text-sm opacity-90">Available balance</div>
            <div class="text-4xl font-bold mt-1">₦{{ number_format($balance) }}</div>
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
