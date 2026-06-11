@extends('layouts.app')
@section('title', 'Leaderboard')
@section('heading', 'Leaderboard')

@section('content')
    <p class="text-slate-500 text-sm mb-5">Top savers by total contributions. Keep paying on time to climb! 🏆</p>

    @if (count($rows))
        @if ($myStanding && $myStanding['rank'] > 20)
            <div class="bg-brand-light border border-brand/30 rounded-2xl p-4 mb-5 flex items-center justify-between max-w-2xl">
                <span class="font-extrabold text-brand-dark">Your rank: #{{ $myStanding['rank'] }}</span>
                <span class="font-extrabold">₦{{ number_format($myStanding['total']) }}</span>
            </div>
        @endif

        <div class="max-w-2xl bg-white border border-slate-200 rounded-2xl divide-y divide-slate-100 overflow-hidden">
            @foreach ($rows as $r)
                <div class="flex items-center gap-4 px-4 py-3 {{ $r['is_me'] ? 'bg-brand-light/50' : '' }}">
                    <div class="w-8 text-center text-lg font-extrabold">
                        @if ($r['rank'] === 1) 🥇
                        @elseif ($r['rank'] === 2) 🥈
                        @elseif ($r['rank'] === 3) 🥉
                        @else <span class="text-slate-400">{{ $r['rank'] }}</span>
                        @endif
                    </div>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 font-bold shrink-0">{{ strtoupper(substr($r['name'], 0, 1)) }}</span>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('users.show', $r['user_id']) }}" class="font-bold truncate hover:underline">{{ $r['name'] }}</a>
                        @if ($r['is_me'])<span class="text-xs text-brand-dark font-bold ml-1">(you)</span>@endif
                    </div>
                    <span class="font-extrabold">₦{{ number_format($r['total']) }}</span>
                </div>
            @endforeach
        </div>
    @else
        <x-empty-state title="No rankings yet"
            message="Once contributions start rolling in, top savers will appear here." />
    @endif
@endsection
