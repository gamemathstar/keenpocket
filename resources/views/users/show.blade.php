@extends('layouts.app')
@section('title', $profileUser->name)
@section('heading', 'Member profile')

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Identity + reputation --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-brand-light text-brand-dark text-xl font-semibold">{{ strtoupper(substr($profileUser->name, 0, 1)) }}</span>
                <div>
                    <div class="font-semibold text-lg">{{ $profileUser->name }}</div>
                    @if ($profileUser->kyc_status === 'verified')
                        <span class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2 py-0.5">✓ Verified</span>
                    @endif
                    @if ($isMe)<span class="text-xs text-slate-400 ml-1">(you)</span>@endif
                </div>
            </div>
            <div class="rounded-lg bg-slate-50 p-4 text-center">
                <div class="text-3xl font-bold text-brand-dark">{{ $rep['score'] }}</div>
                <div class="text-xs text-slate-500">reputation</div>
                <span class="inline-block mt-2 text-xs px-3 py-1 rounded-full bg-brand-light text-brand-dark font-medium">{{ $rep['band'] }}</span>
            </div>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">On-time payments</dt><dd>{{ is_null($rep['payment_reliability']) ? '—' : $rep['payment_reliability'].'%' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Cycles completed</dt><dd>{{ $rep['cycles_completed'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Rating</dt><dd>{{ $rep['rating_average'] ? $rep['rating_average'].' ★ ('.$rep['rating_count'].')' : '—' }}</dd></div>
            </dl>
            @if (count($badges))
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($badges as $b)
                        <span class="text-xs bg-brand-light text-brand-dark rounded-full px-2.5 py-1" title="{{ $b['description'] }}">🏅 {{ $b['label'] }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Their open groups + ratings --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="font-semibold mb-3">Open groups by {{ explode(' ', $profileUser->name)[0] }}</h3>
                @if ($openPockets->isEmpty() && $openAdashis->isEmpty())
                    <p class="text-sm text-slate-500">No open groups right now.</p>
                @else
                    <div class="grid sm:grid-cols-2 gap-3">
                        @foreach ($openPockets as $p)
                            <a href="{{ route('pockets.show', $p->id) }}" class="block rounded-lg border border-slate-200 p-3 hover:border-brand transition">
                                <span class="text-xs text-brand-dark">Pocket</span>
                                <div class="font-medium text-sm truncate">{{ $p->title }}</div>
                                <div class="text-xs text-slate-500">₦{{ number_format($p->amount_per_hand) }}/hand</div>
                            </a>
                        @endforeach
                        @foreach ($openAdashis as $a)
                            <a href="{{ route('adashi.show', $a->id) }}" class="block rounded-lg border border-slate-200 p-3 hover:border-brand transition">
                                <span class="text-xs text-brand-dark">Adashi</span>
                                <div class="font-medium text-sm truncate">{{ $a->name }}</div>
                                <div class="text-xs text-slate-500">₦{{ number_format($a->amount_per_cycle) }}/cycle</div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="font-semibold mb-3">What members say</h3>
                <div class="divide-y divide-slate-100">
                    @forelse ($ratings as $r)
                        <div class="py-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">{{ $r->rater ?? 'Member' }}</span>
                                <span class="text-amber-500">{{ str_repeat('★', $r->stars) }}{{ str_repeat('☆', 5 - $r->stars) }}</span>
                            </div>
                            @if ($r->comment)<p class="text-slate-500 text-xs mt-0.5">{{ $r->comment }}</p>@endif
                        </div>
                    @empty
                        <p class="py-2 text-sm text-slate-500">No ratings yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
