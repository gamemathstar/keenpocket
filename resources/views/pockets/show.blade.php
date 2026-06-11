@extends('layouts.app')
@section('title', $pocket->title)
@section('heading', 'Pocket')

@section('content')
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-brand-light text-brand-dark">{{ $pocket->pocket_type }}</span>
                <h2 class="text-2xl font-semibold mt-2">{{ $pocket->title }}</h2>
                <p class="text-slate-500 text-sm mt-1">{{ $pocket->description ?: 'No description.' }}</p>
                <p class="text-xs text-slate-400 mt-2">Organised by {{ $owner->name ?? '—' }} · {{ $pocket->month_count }} months · {{ $pocket->year }}</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-semibold">₦{{ number_format($pocket->amount_per_hand) }}</div>
                <div class="text-xs text-slate-400">per hand</div>
            </div>
        </div>

        @unless ($isMember)
            <form method="POST" action="{{ route('pockets.join', $pocket->id) }}" class="mt-5 flex items-end gap-3 border-t border-slate-100 pt-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium mb-1">Hands</label>
                    <input type="number" name="hand_count" value="1" min="1" class="w-24 rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:ring-brand">
                </div>
                <button class="rounded-lg bg-brand hover:bg-brand-dark text-white font-medium px-5 py-2.5">Join pocket</button>
            </form>
        @endunless
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Members --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold mb-3">Members ({{ $members->where('status', 1)->count() }})</h3>
            <ul class="divide-y divide-slate-100">
                @forelse ($members as $m)
                    <li class="py-2 flex items-center justify-between text-sm">
                        <span>{{ $m->name }}</span>
                        <span class="text-slate-400">{{ (int) $m->hand_count }} hand(s) · {{ $m->status ? 'active' : 'pending' }}</span>
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-500">No members yet.</li>
                @endforelse
            </ul>
        </div>

        {{-- My invoices --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold">My invoices</h3>
                @if ($isMember)
                    <a href="{{ route('invoices.create', $pocket->id) }}" class="text-sm bg-brand hover:bg-brand-dark text-white rounded-lg px-3 py-1.5">+ Contribution</a>
                @endif
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($invoices as $inv)
                    <li class="py-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">{{ $inv->invoice_no }}</span>
                            <span class="flex items-center gap-2">
                                <span>₦{{ number_format($inv->amount) }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $inv->payment_status === 'Paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $inv->payment_status }}</span>
                            </span>
                        </div>
                        @if ($inv->payment_status !== 'Paid')
                            <div class="flex gap-2 mt-2">
                                @if ($walletEnabled)
                                    <form method="POST" action="{{ route('invoices.payWallet', $inv->id) }}">
                                        @csrf
                                        <button class="text-xs rounded-md bg-brand hover:bg-brand-dark text-white px-3 py-1">Pay from wallet</button>
                                    </form>
                                @endif
                                @if ($isOwner)
                                    <form method="POST" action="{{ route('invoices.markPaid', $inv->id) }}">
                                        @csrf
                                        <button class="text-xs rounded-md border border-slate-300 hover:bg-slate-50 px-3 py-1">Mark as paid</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-500">No invoices yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
