@extends('layouts.app')
@section('title', 'Payouts & Bank')
@section('heading', 'Payouts & Bank details')

@section('content')
    @unless ($enabled)
        <div class="mb-6 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm max-w-3xl">
            Automated payouts aren't switched on yet. You can still save your details now — they'll be used once payouts go live.
        </div>
    @endunless

    <div class="grid lg:grid-cols-2 gap-6 max-w-5xl">
        {{-- Personal payout destination --}}
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6">
            <h3 class="font-semibold mb-1">My payout account</h3>
            <p class="text-sm text-slate-500 mb-4">Where your Adashi payouts are sent.</p>
            <form method="POST" action="{{ route('payouts.saveBank') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Bank name</label>
                    <input name="bank_name" value="{{ old('bank_name', $user->payout_bank_name) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="GTBank">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Bank code</label>
                        <input name="bank_code" value="{{ old('bank_code', $user->payout_bank_code) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="058">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Account number</label>
                        <input name="account_number" value="{{ old('account_number', $user->payout_account_number) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand" placeholder="0123456789">
                    </div>
                </div>
                <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Save account</button>
            </form>
        </div>

        {{-- Payout history --}}
        <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-6">
            <h3 class="font-semibold mb-3">Payouts received</h3>
            <ul class="divide-y divide-slate-100">
                @forelse ($received as $p)
                    <li class="py-2 flex items-center justify-between text-sm">
                        <span class="text-slate-600">{{ $p->reference }}</span>
                        <span class="flex items-center gap-2">
                            <span>₦{{ number_format($p->amount) }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $p->status === 'success' ? 'bg-emerald-100 text-emerald-700' : ($p->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ $p->status }}</span>
                        </span>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">No payouts yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Pocket collection bank details --}}
    <h3 class="font-semibold mt-8 mb-3">Bank details for pockets you organise</h3>
    @if ($ownedPockets->isEmpty())
        <p class="text-sm text-slate-500">You don't organise any pockets yet.</p>
    @else
        <div class="grid md:grid-cols-2 gap-4 max-w-5xl">
            @foreach ($ownedPockets as $p)
                <div class="bg-white rounded-[1.5rem] card-depth border-2 border-slate-100 p-5">
                    <div class="font-medium mb-3">{{ $p->title }}</div>
                    <form method="POST" action="{{ route('payouts.savePocketBank', $p->id) }}" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium mb-1">Bank</label>
                                <input name="bank" value="{{ $p->bank }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Account (NUBAN)</label>
                                <input name="nuban" value="{{ $p->nuban }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                            </div>
                        </div>
                        <button class="text-sm rounded-lg border border-slate-300 hover:bg-slate-50 px-4 py-2">Save</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
@endsection
