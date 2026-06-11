@extends('layouts.app')
@section('title', 'Profile')
@section('heading', 'Profile')

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Identity + reputation --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-brand-light text-brand-dark text-xl font-semibold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                <div>
                    <div class="font-semibold text-lg">{{ $user->name }}</div>
                    <div class="text-sm text-slate-500">{{ $user->phone_number }}</div>
                </div>
            </div>
            <div class="rounded-lg bg-slate-50 p-4 text-center">
                <div class="text-3xl font-bold text-brand-dark">{{ $rep['score'] }}</div>
                <div class="text-xs text-slate-500">reputation score</div>
                <span class="inline-block mt-2 text-xs px-3 py-1 rounded-full bg-brand-light text-brand-dark font-medium">{{ $rep['band'] }}</span>
            </div>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Payment reliability</dt><dd>{{ is_null($rep['payment_reliability']) ? '—' : $rep['payment_reliability'].'%' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Pockets joined</dt><dd>{{ $rep['pockets_joined'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Adashi joined</dt><dd>{{ $rep['adashis_joined'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Cycles completed</dt><dd>{{ $rep['cycles_completed'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Rating</dt><dd>{{ $rep['rating_average'] ? $rep['rating_average'].' ★ ('.$rep['rating_count'].')' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Identity (KYC)</dt><dd class="capitalize">{{ $kyc['status'] }}</dd></div>
            </dl>
        </div>

        {{-- Badges --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold">Achievements</h3>
                @if ($profile)<span class="text-sm text-slate-500">{{ $profile['streak'] }} 🔥 streak · ₦{{ number_format($profile['total_contributed']) }} saved</span>@endif
            </div>
            @if ($profile)
                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach ($profile['badges'] as $b)
                        <div class="flex items-center gap-3 rounded-lg border p-3 {{ $b['earned'] ? 'border-brand bg-brand-light/40' : 'border-slate-200 opacity-60' }}">
                            <span class="text-2xl">{{ $b['earned'] ? '🏅' : '🔒' }}</span>
                            <div>
                                <div class="font-medium text-sm">{{ $b['label'] }}</div>
                                <div class="text-xs text-slate-500">{{ $b['description'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-500">Gamification is disabled.</p>
            @endif

            <h3 class="font-semibold mt-6 mb-3">Ratings received</h3>
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
@endsection
