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
            <div class="flex flex-col items-center py-2">
                <x-progress-ring :percent="$rep['score']" :label="$rep['score']" sublabel="reputation" />
                <span class="inline-block mt-3 text-xs px-3 py-1 rounded-full bg-brand-light text-brand-dark font-bold uppercase tracking-wide">{{ $rep['band'] }}</span>
            </div>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Payment reliability</dt><dd>{{ is_null($rep['payment_reliability']) ? '—' : $rep['payment_reliability'].'%' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Pockets joined</dt><dd>{{ $rep['pockets_joined'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Adashi joined</dt><dd>{{ $rep['adashis_joined'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Cycles completed</dt><dd>{{ $rep['cycles_completed'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Rating</dt><dd>{{ $rep['rating_average'] ? $rep['rating_average'].' ★ ('.$rep['rating_count'].')' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Identity (KYC)</dt><dd class="capitalize">{{ $kyc['enabled'] ? $kyc['status'] : 'not required' }}</dd></div>
            </dl>

            @if ($kyc['enabled'] && $kyc['status'] !== 'verified')
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <h4 class="text-sm font-semibold mb-2">Verify your identity</h4>
                    <form method="POST" action="{{ route('kyc.submit') }}" class="space-y-2">
                        @csrf
                        <div class="flex gap-2">
                            <select name="type" class="rounded-lg border border-slate-300 px-2 py-2 text-sm focus:border-brand focus:ring-brand">
                                <option value="BVN">BVN</option><option value="NIN">NIN</option>
                            </select>
                            <input name="id_number" required placeholder="ID number" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand focus:ring-brand">
                        </div>
                        <button class="w-full rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-medium py-2">Verify</button>
                    </form>
                    <p class="text-[11px] text-slate-400 mt-1">We only store the last 4 digits.</p>
                </div>
            @elseif ($kyc['status'] === 'verified')
                <div class="mt-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">✓ Identity verified</div>
            @endif
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
