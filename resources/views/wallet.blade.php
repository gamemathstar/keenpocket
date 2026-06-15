@extends('layouts.app')
@section('title', 'Wallet')
@section('heading', 'Wallet')

@section('content')
    @unless ($enabled)
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-8 text-center max-w-lg">
            <div class="text-4xl mb-3">💳</div>
            <h3 class="font-semibold text-lg">Wallet is coming soon</h3>
            <p class="text-slate-500 text-sm mt-1">In-app wallet isn't enabled yet. Once on, you'll be able to fund a balance and pay contributions in one tap.</p>
        </div>
    @else
        <div class="grid lg:grid-cols-3 gap-6">
            {{-- Main: balance + activity --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Balance hero --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-brand to-blue-600 text-white rounded-[1.5rem] card-depth-brand p-6 sm:p-7">
                    <div class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide opacity-90">💳 Available balance</div>
                    <div class="text-4xl sm:text-5xl font-extrabold mt-2">₦{{ number_format($balance) }}</div>
                    <p class="text-sm opacity-90 mt-2">Use your balance to pay contributions in one tap.</p>
                </div>

                {{-- Recent activity --}}
                <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 overflow-hidden">
                    <h3 class="font-extrabold px-5 pt-5 pb-2">Recent activity</h3>
                    <div class="divide-y divide-slate-100">
                        @forelse ($transactions as $t)
                            @php $credit = $t->type === 'credit'; @endphp
                            <div class="px-5 py-3 flex items-center gap-3 text-sm">
                                <div class="h-10 w-10 shrink-0 rounded-2xl flex items-center justify-center text-lg {{ $credit ? 'bg-emerald-100' : 'bg-slate-100' }}">{{ $credit ? '⬇️' : '⬆️' }}</div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold capitalize truncate">{{ $t->reason }}</div>
                                    <div class="text-xs text-slate-400">{{ $t->created_at }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="{{ $credit ? 'text-emerald-600' : 'text-slate-700' }} font-extrabold">{{ $credit ? '+' : '−' }}₦{{ number_format($t->amount) }}</div>
                                    <div class="text-xs text-slate-400">bal ₦{{ number_format($t->balance_after) }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-sm text-slate-500 text-center">No transactions yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Sidebar: quick top up --}}
            <aside class="space-y-6">
                <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
                    <h3 class="font-extrabold mb-1">💸 Quick top up</h3>
                    <p class="text-xs text-slate-500 mb-3">Add funds to your wallet.</p>
                    <form method="POST" action="{{ route('wallet.topup') }}" class="space-y-3">
                        @csrf
                        <input id="walletAmount" type="number" name="amount" value="5000" min="1"
                               class="w-full rounded-xl border-2 border-slate-200 px-4 py-2.5 text-lg font-bold focus:border-brand focus:ring-brand">
                        <div class="grid grid-cols-3 gap-2">
                            @foreach ([1000, 5000, 10000] as $preset)
                                <button type="button" onclick="document.getElementById('walletAmount').value={{ $preset }}"
                                        class="rounded-xl border-2 border-slate-200 hover:border-brand text-sm font-bold py-1.5">₦{{ number_format($preset) }}</button>
                            @endforeach
                        </div>
                        <button class="w-full rounded-xl bg-brand hover:bg-brand-dark text-white font-bold py-2.5">Add funds</button>
                    </form>
                </div>
                <div class="bg-brand-light rounded-[1.5rem] border-2 border-brand/20 p-5 flex items-start gap-3">
                    <span class="text-2xl">🛡️</span>
                    <p class="text-xs text-slate-600">KeenPocket keeps the records — wallet balances are for paying contributions, not a bank account.</p>
                </div>
            </aside>
        </div>
    @endunless
@endsection
